<?php

/**
 * 溢价域名检测代理（逐域名，双源并行竞速）
 *
 * 与 price.php（后缀级通用价）不同，本接口检测「具体域名」本身是否为
 * 溢价（premium）域名，并返回其溢价价格（注册 / 续费 / 转移）。
 *
 * 数据源（并行竞速，谁先确认溢价用谁的，主打速度）：
 *   - Netim（代理 REST API）：/domain/{name}/price/ 返回 IsPremium 标记与
 *     Fee4Registration / Fee4Renewal / Fee4Transfer 及币种（需先建会话拿 token）。
 *   - Dynadot（search API）：单发请求，返回可注册性与溢价价格。
 *
 * 竞速策略：用 curl_multi 同时发出 Dynadot 请求与 Netim 建会话请求；
 * Netim 拿到 token 后立即并发价格请求。任一源率先「确认溢价且带价格」
 * 即刻采用并返回，另一源放弃（不再等待）。二者都未命中溢价时，
 * 合并可注册性等信息返回非溢价结果。
 *
 * 溢价域名的价格「接管」普通后缀价：命中溢价时，前端用本接口返回的
 * 注册 / 续费价替换 price.php 的通用价，并展示金色溢价徽章；
 * 普通（非溢价）域名不受影响，仍由 price.php 正常展示。
 *
 * 价格统一附带人民币估算（*_cny），汇率每日缓存一次。
 * 密钥仅在服务端读取（NETIM_API_LOGIN / NETIM_API_SECRET / DYNADOT_API_KEY），
 * 任一数据源缺凭证或失败时自动跳过，优雅降级，绝不因单源失败而报错。
 *
 * 请求: GET /premium?domain=example.com
 * 响应: { code, domain, premium, available, currency,
 *         register, renew, transfer,
 *         register_cny, renew_cny, transfer_cny, source }
 */

header("Content-Type: application/json; charset=utf-8");

// ---- 解析并校验域名 ----------------------------------------------------------
$domain = isset($_GET["domain"]) ? strtolower(trim($_GET["domain"])) : "";
$domain = trim(preg_replace('/\s+/', "", $domain), ".");

// IDN 转 punycode，保证向上游 API 提交 ASCII 域名
if ($domain !== "" && function_exists("idn_to_ascii")) {
    $ascii = idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
    if ($ascii) {
        $domain = strtolower($ascii);
    }
}

// 必须是形如 name.tld 的合法域名（含至少一个点）
if ($domain === "" || !preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?(\.[a-z0-9-]+)+$/', $domain)) {
    http_response_code(400);
    echo json_encode(["code" => 400, "message" => "invalid domain"]);
    exit;
}

// ---- 服务端缓存（按完整域名）------------------------------------------------
// 溢价属性与价格变动较慢，缓存可将重复查询从数秒降到毫秒级，并减轻上游限速压力。
define("PREMIUM_CACHE_TTL", 43200); // 12 小时
$cacheDir = sys_get_temp_dir() . "/nw_premium_cache";
$cacheKey = preg_replace('/[^a-z0-9.-]/', "_", $domain);
$cacheFile = $cacheDir . "/" . $cacheKey . ".json";

if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < PREMIUM_CACHE_TTL) {
    $cached = file_get_contents($cacheFile);
    if ($cached !== false && $cached !== "") {
        header("X-Premium-Cache: HIT");
        header("Cache-Control: public, max-age=3600");
        echo $cached;
        exit;
    }
}

/** 写入缓存并输出，然后退出 */
function premium_emit($payload, $cacheDir, $cacheFile, $maxAge = 3600)
{
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0777, true);
    }
    @file_put_contents($cacheFile, $payload, LOCK_EX);
    header("X-Premium-Cache: MISS");
    header("Cache-Control: public, max-age=" . (int) $maxAge);
    echo $payload;
    exit;
}

// ---- 单次 HTTP 请求（仅用于汇率等非竞速场景）-------------------------------
function premium_http($method, $url, $opts = [])
{
    $ch = premium_curl_handle($url, array_merge($opts, ["method" => $method]));
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($resp === false || $code < 200 || $code >= 300) {
        return null;
    }
    $json = json_decode($resp, true);
    return is_array($json) ? $json : null;
}

// ---- 构建 curl 句柄（供单发与 curl_multi 并发复用）-------------------------
function premium_curl_handle($url, $opts = [])
{
    $ch = curl_init($url);
    $headers = array_merge(
        ["Accept: application/json", "Content-Type: application/json"],
        $opts["headers"] ?? []
    );
    $curl = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $opts["method"] ?? "GET",
        CURLOPT_TIMEOUT        => $opts["timeout"] ?? 7,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => "Mozilla/5.0 (compatible; WhoisNic/1.0)",
        CURLOPT_HTTPHEADER     => $headers,
    ];
    if (!empty($opts["userpwd"])) {
        $curl[CURLOPT_USERPWD] = $opts["userpwd"];
    }
    if (isset($opts["body"]) && $opts["body"] !== null) {
        $curl[CURLOPT_POSTFIELDS] = is_string($opts["body"]) ? $opts["body"] : json_encode($opts["body"]);
    }
    curl_setopt_array($ch, $curl);
    return $ch;
}

// ---- 金额解析：从字符串或数字提取浮点金额（兼容 "455.00" / "2,999.99"）------
function premium_num($v)
{
    if ($v === null || $v === "") {
        return null;
    }
    if (is_numeric($v)) {
        $n = (float) $v;
        return $n > 0 ? $n : null;
    }
    if (preg_match('/([0-9][0-9,]*(?:\.[0-9]+)?)/', (string) $v, $m)) {
        $n = (float) str_replace(",", "", $m[1]);
        return $n > 0 ? $n : null;
    }
    return null;
}

// ---- 大小写无关取值助手 ------------------------------------------------------
function premium_get($arr, $keys)
{
    if (!is_array($arr)) {
        return null;
    }
    foreach ((array) $keys as $k) {
        foreach ($arr as $rk => $rv) {
            if (strcasecmp((string) $rk, $k) === 0) {
                return $rv;
            }
        }
    }
    return null;
}

// ---- 汇率：外币 → 人民币（每日缓存，失败回退近似值）--------------------------
function premium_fx_rate($currency)
{
    $currency = strtoupper((string) $currency);
    if ($currency === "" || $currency === "CNY") {
        return $currency === "CNY" ? 1.0 : null;
    }

    // 近似兜底汇率（上游不可达时使用，避免完全无换算）
    static $fallback = ["USD" => 7.20, "EUR" => 7.80, "GBP" => 9.10, "HKD" => 0.92, "AUD" => 4.70, "CAD" => 5.20];

    // 每日缓存全量汇率表（base=USD）
    static $rates = null;
    if ($rates === null) {
        $rates = [];
        $fxDir = sys_get_temp_dir() . "/nw_premium_cache";
        $fxFile = $fxDir . "/_fx_usd.json";
        if (is_file($fxFile) && (time() - filemtime($fxFile)) < 86400) {
            $cached = json_decode((string) file_get_contents($fxFile), true);
            if (is_array($cached) && !empty($cached["rates"])) {
                $rates = $cached["rates"];
            }
        }
        if (empty($rates)) {
            // 免费、无需密钥的汇率源（base=USD）
            $fx = premium_http("GET", "https://open.er-api.com/v6/latest/USD", ["timeout" => 5]);
            if ($fx && !empty($fx["rates"]) && is_array($fx["rates"])) {
                $rates = $fx["rates"];
                if (!is_dir($fxDir)) {
                    @mkdir($fxDir, 0777, true);
                }
                @file_put_contents($fxFile, json_encode(["rates" => $rates]), LOCK_EX);
            }
        }
    }

    // rates 以 USD 为基准：CNY/该币种 = rates[CNY] / rates[currency]
    if (!empty($rates["CNY"]) && !empty($rates[$currency])) {
        $r = (float) $rates["CNY"] / (float) $rates[$currency];
        if ($r > 0) {
            return $r;
        }
    }
    return $fallback[$currency] ?? null;
}

function premium_to_cny($price, $currency)
{
    if ($price === null || $currency === null) {
        return null;
    }
    if (strtoupper($currency) === "CNY") {
        return round((float) $price, 2);
    }
    $rate = premium_fx_rate($currency);
    return $rate === null ? null : round((float) $price * $rate, 2);
}

// ---- 响应解析：Netim /price/ → 统一结构 -------------------------------------
function premium_parse_netim($json)
{
    if (!is_array($json)) {
        return null;
    }
    $isPremiumRaw = premium_get($json, ["IsPremium", "isPremium", "premium"]);
    $premium = in_array(strtolower((string) $isPremiumRaw), ["1", "yes", "true"], true) || (int) $isPremiumRaw === 1;

    $currency = premium_get($json, ["FeeCurrency", "currency", "Currency"]);
    return [
        "available" => null,
        "premium"   => $premium,
        "currency"  => $currency ? strtoupper($currency) : null,
        "register"  => premium_num(premium_get($json, ["Fee4Registration", "fee4Registration", "registration"])),
        "renew"     => premium_num(premium_get($json, ["Fee4Renewal", "fee4Renewal", "renewal"])),
        "transfer"  => premium_num(premium_get($json, ["Fee4Transfer", "fee4Transfer", "transfer"])),
    ];
}

// ---- 响应解析：Dynadot search → 统一结构 ------------------------------------
function premium_parse_dynadot($json)
{
    if (!is_array($json)) {
        return null;
    }

    // 定位结果对象（兼容多种外层结构）
    $result = null;
    foreach ([
        ["SearchResponse", "SearchResults", 0],
        ["data", 0],
        ["SearchResults", 0],
    ] as $path) {
        $cur = $json;
        foreach ($path as $p) {
            if (is_array($cur) && isset($cur[$p])) {
                $cur = $cur[$p];
            } else {
                $cur = null;
                break;
            }
        }
        if (is_array($cur)) {
            $result = $cur;
            break;
        }
    }
    if ($result === null && premium_get($json, ["Available", "available"]) !== null) {
        $result = $json;
    }
    if (!is_array($result)) {
        return null;
    }

    $availRaw = strtolower((string) premium_get($result, ["Available", "available"]));
    $available = $availRaw === "" ? null : in_array($availRaw, ["yes", "true", "1"], true);

    $premRaw = strtolower((string) premium_get($result, ["IsPremium", "Premium", "premium", "is_premium"]));
    $premium = in_array($premRaw, ["yes", "true", "1"], true);

    $register = premium_num(premium_get($result, ["Price", "price", "PremiumPrice", "premium_price", "registration"]));
    // Dynadot 溢价的强信号：注册价明显高于普通价
    if (!$premium && $register !== null && $register >= 100) {
        $premium = true;
    }

    return [
        "available" => $available,
        "premium"   => $premium,
        "currency"  => "USD",
        "register"  => $register,
        "renew"     => null,
        "transfer"  => null,
    ];
}

/**
 * 双源并行竞速检测。
 * 用 curl_multi 同时发出 Dynadot 请求与 Netim 建会话请求；Netim 拿到 token
 * 后立即并发价格请求。任一源率先「确认溢价且带价格」即刻返回该结果。
 *
 * @return array [$netimResult|null, $dynadotResult|null, $winnerSource]
 */
function premium_race($domain)
{
    $netimBase = "https://rest.netim.com/1.0";
    $dynaKey   = getenv("DYNADOT_API_KEY");
    $nLogin    = getenv("NETIM_API_LOGIN");
    $nSecret   = getenv("NETIM_API_SECRET");

    $mh = curl_multi_init();
    $handles = [];   // (int)$ch => ["ch"=>resource, "kind"=>string]
    $results = ["netim" => null, "dynadot" => null];
    $dynaLegacyTried = false;
    $winner = "";

    $addHandle = function ($ch, $kind) use ($mh, &$handles) {
        curl_multi_add_handle($mh, $ch);
        $handles[(int) $ch] = ["ch" => $ch, "kind" => $kind];
    };

    // 起跑：Dynadot 单发（新版 RESTful，失败再回退旧版）
    if ($dynaKey) {
        $addHandle(premium_curl_handle(
            "https://api.dynadot.com/restful/v1/domains/" . rawurlencode($domain) . "/search?show_price=true&currency=USD",
            ["headers" => ["Authorization: Bearer " . $dynaKey], "timeout" => 7]
        ), "dynadot");
    }
    // 起跑：Netim 建会话（拿到 token 后再并发价格请求）
    if ($nLogin && $nSecret) {
        $addHandle(premium_curl_handle(
            $netimBase . "/session/",
            ["method" => "POST", "userpwd" => $nLogin . ":" . $nSecret, "timeout" => 7]
        ), "netim-session");
    }

    if (empty($handles)) {
        curl_multi_close($mh);
        return [null, null, ""];
    }

    // 事件循环：处理率先完成的请求，动态追加 Netim 价格 / Dynadot 旧版兜底。
    // 以「是否仍有未完成句柄」为循环条件，确保新追加的请求一定会被执行，
    // 不依赖 $active 计数（避免同轮全部完成后新句柄被漏跑）。
    $active = null;
    do {
        do {
            $status = curl_multi_exec($mh, $active);
        } while ($status === CURLM_CALL_MULTI_PERFORM);

        while ($info = curl_multi_info_read($mh)) {
            $ch = $info["handle"];
            $id = (int) $ch;
            $kind = $handles[$id]["kind"] ?? "";
            $body = curl_multi_getcontent($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
            unset($handles[$id]);

            $json = ($body !== false && $body !== "" && $code >= 200 && $code < 300)
                ? json_decode($body, true) : null;

            if ($kind === "dynadot") {
                if (!is_array($json) && !$dynaLegacyTried && $dynaKey) {
                    // 新版失败 → 并发回退旧版 api3.json
                    $dynaLegacyTried = true;
                    $addHandle(premium_curl_handle(
                        "https://api.dynadot.com/api3.json?key=" . urlencode($dynaKey)
                            . "&command=search&show_price=1&currency=USD&domain0=" . urlencode($domain),
                        ["timeout" => 7]
                    ), "dynadot");
                } else {
                    $results["dynadot"] = premium_parse_dynadot($json);
                }
            } elseif ($kind === "netim-session") {
                $token = is_array($json)
                    ? premium_get($json, ["access_token", "id", "sessionId", "session_id", "token"]) : null;
                if ($token) {
                    $addHandle(premium_curl_handle(
                        $netimBase . "/domain/" . rawurlencode($domain) . "/price/",
                        ["headers" => ["Authorization: Bearer " . $token], "timeout" => 7]
                    ), "netim-price");
                }
            } elseif ($kind === "netim-price") {
                $results["netim"] = premium_parse_netim($json);
            }

            // 竞速裁决：任一源率先「确认溢价且带注册价」立即夺冠
            foreach (["netim", "dynadot"] as $src) {
                $r = $results[$src];
                if ($r !== null && $r["premium"] && $r["register"] !== null) {
                    $winner = $src;
                    break;
                }
            }
            if ($winner !== "") {
                break;
            }
        }

        if ($winner !== "") {
            break;
        }
        // 仍有未完成句柄则等待活动（select 立即返回时靠 exec 推进，循环上限受各请求超时约束）
        if (!empty($handles)) {
            if (curl_multi_select($mh, 1.0) === -1) {
                usleep(20000); // 20ms，避免 select 立即返回时空转
            }
        }
    } while (!empty($handles));

    // 清理未完成句柄（夺冠后放弃其余请求）
    foreach ($handles as $h) {
        curl_multi_remove_handle($mh, $h["ch"]);
        curl_close($h["ch"]);
    }
    curl_multi_close($mh);

    return [$results["netim"], $results["dynadot"], $winner];
}

// ---- 执行竞速并聚合 ----------------------------------------------------------
[$netim, $dyna, $winner] = premium_race($domain);

// 两源都不可用：明确返回未启用 / 无数据
if ($netim === null && $dyna === null) {
    premium_emit(json_encode([
        "code" => 200, "domain" => $domain, "premium" => false, "available" => null,
        "currency" => null, "register" => null, "renew" => null, "transfer" => null,
        "register_cny" => null, "renew_cny" => null, "transfer_cny" => null, "source" => "",
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $cacheDir, $cacheFile, 1800);
}

$premium = false;
$available = null;
$currency = null;
$register = $renew = $transfer = null;
$source = "";

// 竞速优胜者优先（谁先确认溢价用谁的）；否则按 Netim（价格更全）→ Dynadot 顺序择优
$order = $winner !== ""
    ? [$winner, $winner === "netim" ? "dynadot" : "netim"]
    : ["netim", "dynadot"];

$byName = ["netim" => $netim, "dynadot" => $dyna];
foreach ($order as $name) {
    $d = $byName[$name];
    if ($d === null) {
        continue;
    }
    if ($available === null && $d["available"] !== null) {
        $available = $d["available"];
    }
    if ($d["premium"] && !$premium) {
        $premium = true;
        $currency = $d["currency"];
        $register = $d["register"];
        $renew = $d["renew"];
        $transfer = $d["transfer"];
        $source = $name;
    }
}

// 若判为溢价但优胜源缺某项价格，用另一源补全
if ($premium) {
    foreach ($byName as $d) {
        if ($d === null || !$d["premium"]) {
            continue;
        }
        if ($register === null && $d["register"] !== null) {
            $register = $d["register"];
            if ($currency === null) {
                $currency = $d["currency"];
            }
        }
        if ($renew === null && $d["renew"] !== null) {
            $renew = $d["renew"];
        }
        if ($transfer === null && $d["transfer"] !== null) {
            $transfer = $d["transfer"];
        }
    }
}

$payload = json_encode([
    "code"         => 200,
    "domain"       => $domain,
    "premium"      => $premium,
    "available"    => $available,
    "currency"     => $premium ? $currency : null,
    "register"     => $premium ? $register : null,
    "renew"        => $premium ? $renew : null,
    "transfer"     => $premium ? $transfer : null,
    "register_cny" => $premium ? premium_to_cny($register, $currency) : null,
    "renew_cny"    => $premium ? premium_to_cny($renew, $currency) : null,
    "transfer_cny" => $premium ? premium_to_cny($transfer, $currency) : null,
    "source"       => $source,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

premium_emit($payload, $cacheDir, $cacheFile, 3600);
