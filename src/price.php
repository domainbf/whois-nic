<?php

/**
 * 域名价格智能代理
 *
 * 优先使用「米情局」(miqingju) 直接获取每种类型(注册/续费/转移)的全网最低价，
 * 失败或无数据时回退到「哪煮米」(nazhumi)。
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

// ---- HTTP GET 辅助 -----------------------------------------------------------
function price_http_get($url, $timeout = 8)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => "Mozilla/5.0 (compatible; WhoisNic/1.0)",
        CURLOPT_HTTPHEADER => ["Accept: application/json"],
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($body === false || $code < 200 || $code >= 300) {
        return null;
    }
    $json = json_decode($body, true);
    return is_array($json) ? $json : null;
}

function price_entry($price, $currency, $cny, $registrar, $website = "")
{
    $currency = strtoupper((string)$currency);
    return [
        "price" => $price !== null ? round((float)$price, 2) : null,
        "currency" => $currency,
        // 人民币换算值（米情局直接提供，哪煮米无则为 null）
        "price_cny" => $cny !== null ? round((float)$cny, 2) : null,
        "registrar" => $registrar ?: "",
        "website" => $website ?: "",
    ];
}

// ---- 数据源 1: 米情局 --------------------------------------------------------
function fetch_miqingju($tld)
{
    $json = price_http_get("https://api.miqingju.com/api/v1/query?tld=" . urlencode($tld));
    if (!$json || empty($json["success"]) || empty($json["data"]) || !is_array($json["data"])) {
        return null;
    }

    $map = ["registration" => "register", "renewal" => "renew", "transfer" => "transfer"];
    $data = [];
    foreach ($json["data"] as $item) {
        if (!isset($item["type"], $map[$item["type"]])) {
            continue;
        }
        $key = $map[$item["type"]];
        $data[$key] = price_entry(
            $item["price"] ?? null,
            $item["currency"] ?? "",
            $item["price_cny"] ?? null,
            $item["registrar"] ?? "",
            $item["website"] ?? ""
        );
    }

    if (empty($data)) {
        return null;
    }
    return ["source" => "miqingju", "data" => $data];
}

// ---- 数据源 2: 哪煮米 (回退) -------------------------------------------------
function fetch_nazhumi($tld)
{
    $orders = ["register" => "new", "renew" => "renew", "transfer" => "transfer"];
    $data = [];
    foreach ($orders as $key => $order) {
        $json = price_http_get("https://www.nazhumi.com/api/v1?domain=" . urlencode($tld) . "&order=" . $order, 7);
        if (!$json || ($json["code"] ?? 0) != 100 || empty($json["data"]["price"][0])) {
            continue;
        }
        $top = $json["data"]["price"][0];
        $field = $order; // new / renew / transfer
        $cur = strtoupper($top["currency"] ?? "");
        $val = $top[$field] ?? null;
        $data[$key] = price_entry(
            $val,
            $cur,
            $cur === "CNY" ? $val : null, // 仅人民币原价可直接作为 cny 值
            $top["registrarname"] ?? ($top["registrar"] ?? ""),
            $top["registrarweb"] ?? ""
        );
    }

    if (empty($data)) {
        return null;
    }
    return ["source" => "nazhumi", "data" => $data];
}

// ---- 智能获取: 优先米情局，回退哪煮米 ---------------------------------------
$result = fetch_miqingju($tld);
if ($result === null) {
    $result = fetch_nazhumi($tld);
}

if ($result === null) {
    http_response_code(502);
    echo json_encode(["code" => 502, "message" => "no price data", "tld" => $tld]);
    exit;
}

echo json_encode([
    "code" => 200,
    "source" => $result["source"],
    "tld" => $tld,
    "data" => $result["data"],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
