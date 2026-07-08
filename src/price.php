<?php

/**
 * 域名价格智能代理
 *
 * 同时向「米情局」(miqingju) 与「哪煮米」(nazhumi) 并发请求（curl_multi），
 * 对每种类型(注册/续费/转移)聚合两个数据源各自的全网最低价并取平均，
 * 以平滑单一数据源的促销/过期异常值，提升价格准确性。
 * 统一输出标准化 JSON，避免浏览器端跨域(miqingju 不支持 CORS)。
 *
 * 请求: GET /price?domain=example.com  (或 ?tld=com)
 * 响应: { code, source, tld, data: { register, renew, transfer } }
 */

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: public, max-age=3600");

// ---- 解析 TLD ----------------------------------------------------------------
$tld = "";
if (isset($_GET["tld"]) && $_GET["tld"] !== "") {
    $tld = strtolower(trim($_GET["tld"]));
} elseif (isset($_GET["domain"]) && $_GET["domain"] !== "") {
    $domain = strtolower(trim($_GET["domain"]));
    // 取首个点之后的全部内容作为后缀，兼容 co.uk 等多级后缀
    $dot = strpos($domain, ".");
    $tld = $dot === false ? $domain : substr($domain, $dot + 1);
}
$tld = preg_replace("/^\.+/", "", $tld);

if ($tld === "" || !preg_match("/^[a-z0-9.-]+$/", $tld)) {
    http_response_code(400);
    echo json_encode(["code" => 400, "message" => "invalid tld"]);
    exit;
}

// ---- 服务端缓存（按 TLD）----------------------------------------------------
// 价格几乎按天变动，缓存聚合结果可将同后缀的后续查询从数秒降到毫秒级，
// 这是价格加载最主要的提速手段（上游米情局/哪煮米本身较慢且无 CORS）。
define("PRICE_CACHE_TTL", 43200); // 12 小时
$cacheDir = sys_get_temp_dir() . "/nw_price_cache";
$cacheFile = $cacheDir . "/" . preg_replace("/[^a-z0-9.-]/", "_", $tld) . ".json";

// 命中新鲜缓存：直接返回，附带 X-Price-Cache: HIT 便于排查
if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < PRICE_CACHE_TTL) {
    $cached = file_get_contents($cacheFile);
    if ($cached !== false && $cached !== "") {
        header("X-Price-Cache: HIT");
        echo $cached;
        exit;
    }
}

// 将聚合结果写入缓存（成功结果才缓存）
function price_write_cache($dir, $file, $payload)
{
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    if (is_dir($dir)) {
        @file_put_contents($file, $payload, LOCK_EX);
    }
}

// ---- 并发 HTTP GET（curl_multi）---------------------------------------------
// $requests: [key => url]；返回 [key => 解析后的数组|null]
function price_http_multi(array $requests, $timeout = 5)
{
    $mh = curl_multi_init();
    $handles = [];
    foreach ($requests as $key => $url) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => "Mozilla/5.0 (compatible; WhoisNic/1.0)",
            CURLOPT_HTTPHEADER => ["Accept: application/json"],
        ]);
        curl_multi_add_handle($mh, $ch);
        $handles[$key] = $ch;
    }

    // 并发执行，直到全部完成
    $running = null;
    do {
        $mrc = curl_multi_exec($mh, $running);
        // select 无描述符时会立即返回 -1，需短暂休眠避免 CPU 空转；
        // $mrc === CURLM_OK 作为护栏，避免 libcurl 出错时死循环。
        if ($running > 0 && curl_multi_select($mh, 0.5) === -1) {
            usleep(1000);
        }
    } while ($running > 0 && $mrc === CURLM_OK);

    $out = [];
    foreach ($handles as $key => $ch) {
        $body = curl_multi_getcontent($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $out[$key] = ($body !== false && $code >= 200 && $code < 300)
            ? json_decode($body, true)
            : null;
        if (!is_array($out[$key])) {
            $out[$key] = null;
        }
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    curl_multi_close($mh);
    return $out;
}

// ---- 解析「米情局」：每种类型的最低价（含人民币换算）------------------------
function parse_miqingju($json)
{
    if (!$json || empty($json["success"]) || empty($json["data"]) || !is_array($json["data"])) {
        return [];
    }
    $map = ["registration" => "register", "renewal" => "renew", "transfer" => "transfer"];
    $out = [];
    foreach ($json["data"] as $item) {
        if (!isset($item["type"], $map[$item["type"]])) {
            continue;
        }
        $key = $map[$item["type"]];
        $out[$key] = [
            "price" => isset($item["price"]) ? (float)$item["price"] : null,
            "currency" => strtoupper($item["currency"] ?? ""),
            "price_cny" => isset($item["price_cny"]) ? (float)$item["price_cny"] : null,
            "registrar" => $item["registrar"] ?? "",
            "website" => $item["website"] ?? "",
        ];
    }
    return $out;
}

// ---- 解析「哪煮米」单个订单类型：取最低价一条 --------------------------------
function parse_nazhumi_one($json, $field)
{
    if (!$json || ($json["code"] ?? 0) != 100 || empty($json["data"]["price"][0])) {
        return null;
    }
    $top = $json["data"]["price"][0];
    $cur = strtoupper($top["currency"] ?? "");
    $val = isset($top[$field]) ? (float)$top[$field] : null;
    return [
        "price" => $val,
        "currency" => $cur,
        // 仅人民币原价可直接作为 cny 值（其余币种哪煮米不提供换算）
        "price_cny" => $cur === "CNY" ? $val : null,
        "registrar" => $top["registrarname"] ?? ($top["registrar"] ?? ""),
        "website" => $top["registrarweb"] ?? "",
    ];
}

// ---- 聚合单一类型：对可比的人民币价格取平均，否则回退单源 --------------------
function aggregate_entry($mq, $nz)
{
    // 收集可用于取平均的人民币价格（两源各自的最低价）
    $cnyVals = [];
    if ($mq && $mq["price_cny"] !== null) {
        $cnyVals[] = $mq["price_cny"];
    }
    if ($nz && $nz["price_cny"] !== null) {
        $cnyVals[] = $nz["price_cny"];
    }

    // 注册商信息优先取米情局（通常为全网最低），否则取哪煮米（均做空值保护，避免告警污染 JSON）
    $registrar = ($mq && !empty($mq["registrar"]))
        ? $mq["registrar"]
        : (($nz && !empty($nz["registrar"])) ? $nz["registrar"] : "");
    $website = ($mq && !empty($mq["website"]))
        ? $mq["website"]
        : (($nz && !empty($nz["website"])) ? $nz["website"] : "");

    if (count($cnyVals) > 0) {
        $avg = array_sum($cnyVals) / count($cnyVals);
        return [
            "price" => round($avg, 2),
            "currency" => "CNY",
            "price_cny" => round($avg, 2),
            "registrar" => $registrar ?: "",
            "website" => $website ?: "",
            // 取平均的样本数（前端可据此提示“综合 N 个数据源”）
            "samples" => count($cnyVals),
        ];
    }

    // 无可比人民币价格：回退到有数据的一源（原币种展示）
    $src = $mq ?: $nz;
    if (!$src || $src["price"] === null) {
        return null;
    }
    return [
        "price" => round((float)$src["price"], 2),
        "currency" => $src["currency"],
        "price_cny" => $src["price_cny"] !== null ? round((float)$src["price_cny"], 2) : null,
        "registrar" => $registrar ?: "",
        "website" => $website ?: "",
        "samples" => 1,
    ];
}

// ---- 主流程：并发抓取 → 解析 → 聚合 ----------------------------------------
$e = urlencode($tld);
$responses = price_http_multi([
    "miqingju"    => "https://api.miqingju.com/api/v1/query?tld=" . $e,
    "nz_register" => "https://www.nazhumi.com/api/v1?domain=" . $e . "&order=new",
    "nz_renew"    => "https://www.nazhumi.com/api/v1?domain=" . $e . "&order=renew",
    "nz_transfer" => "https://www.nazhumi.com/api/v1?domain=" . $e . "&order=transfer",
]);

$mqData = parse_miqingju($responses["miqingju"] ?? null);
$nzData = [
    "register" => parse_nazhumi_one($responses["nz_register"] ?? null, "new"),
    "renew"    => parse_nazhumi_one($responses["nz_renew"] ?? null, "renew"),
    "transfer" => parse_nazhumi_one($responses["nz_transfer"] ?? null, "transfer"),
];

$data = [];
$usedSources = [];
foreach (["register", "renew", "transfer"] as $key) {
    $mq = $mqData[$key] ?? null;
    $nz = $nzData[$key] ?? null;
    $entry = aggregate_entry($mq, $nz);
    if ($entry !== null) {
        if ($mq) { $usedSources["miqingju"] = true; }
        if ($nz) { $usedSources["nazhumi"] = true; }
        // 输出时去掉内部字段 samples 之外的信息保持兼容
        $data[$key] = $entry;
    }
}

if (empty($data)) {
    http_response_code(502);
    echo json_encode(["code" => 502, "message" => "no price data", "tld" => $tld]);
    exit;
}

$payload = json_encode([
    "code" => 200,
    "source" => implode("+", array_keys($usedSources)) ?: "unknown",
    "tld" => $tld,
    "data" => $data,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// 写入缓存后返回（附 X-Price-Cache: MISS）
price_write_cache($cacheDir, $cacheFile, $payload);
header("X-Price-Cache: MISS");
echo $payload;
