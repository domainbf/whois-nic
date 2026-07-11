<?php

/**
 * 溢价域名检测代理（逐域名）
 *
 * 与 price.php（后缀级通用价）不同，本接口检测「具体域名」本身是否为
 * 溢价（premium）域名，并返回其溢价价格（注册 / 续费 / 转移）。
 *
 * 数据源：
 *   - 主源 Netim（代理 REST API）：/domain/{name}/price/ 直接返回
 *     IsPremium 标记与 Fee4Registration / Fee4Renewal / Fee4Transfer 及币种，
 *     是最完整、最权威的溢价价格来源。
 *   - 补充源 Dynadot（search API）：用于交叉验证 / 在 Netim 不可用时兜底。
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
 * 响应: {
 *   code: 200,
 *   domain: "b.tools",
 *   premium: true|false,          // 是否为溢价域名
 *   available: true|false|null,   // 是否可注册（null=未知）
 *   currency: "EUR"|null,         // 原始币种
 *   register: 455.00|null,        // 溢价注册价（原币种）
 *   renew: 455.00|null,           // 溢价续费价（原币种）
 *   transfer: 455.00|null,        // 溢价转移价（原币种）
 *   register_cny: 3549.00|null,   // 注册价人民币估算
 *   renew_cny: 3549.00|null,      // 续费价人民币估算
 *   transfer_cny: 3549.00|null,   // 转移价人民币估算
 *   source: "netim"|"dynadot"|""  // 采用的数据源
 * }
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

// ---- 通用 HTTP 请求（GET / POST / DELETE，支持 Basic / Bearer）--------------
function premium_http($method, $url, $opts = [])
{
    $ch = curl_init($url);
    $headers = array_merge(
        ["Accept: application/json", "Content-Type: application/json"],
        $opts["headers"] ?? []
    );
    $curl = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_TIMEOUT        => $opts["timeout"] ?? 8,
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
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($resp === false || $code < 200 || $code >= 300) {
        return null;
    }
    $json = json_decode($resp, true);
    return is_array($json) ? $json : null;
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

// ---- 数据源 1：Netim REST（主源）--------------------------------------------
// 先用 Basic Auth 建立会话拿 access_token，再用 Bearer 查询：
//   /domain/{name}/price/  → IsPremium + Fee4Registration/Renewal/Transfer + FeeCurrency
// 返回统一结构或 null。
function premium_from_netim($domain)
{
    $login = getenv("NETIM_API_LOGIN");
    $secret = getenv("NETIM_API_SECRET");
    if (!$login || !$secret) {
        return null;
    }
    $base = "https://rest.netim.com/1.0";

    // 1) 建立会话（HTTP Basic Auth）
    $session = premium_http("POST", $base . "/session/", [
        "userpwd" => $login . ":" . $secret,
        "timeout" => 8,
    ]);
    if (!$session) {
        return null;
    }
    $token = premium_get($session, ["access_token", "id", "sessionId", "session_id", "token"]);
    if (!$token) {
        return null;
    }
    $auth = ["headers" => ["Authorization: Bearer " . $token], "timeout" => 8];

    // 2) 价格 / 溢价（权威）：/price/ 直接返回 IsPremium + Fee4Registration/Renewal/Transfer + FeeCurrency
    //    可注册性由页面上下文已知，无需额外 /check/ 调用，减少上游请求与限速压力。
    $price = premium_http("GET", $base . "/domain/" . rawurlencode($domain) . "/price/", $auth);

    // 3) 主动关闭会话（尽力而为）
    premium_http("DELETE", $base . "/session/", $auth);

    if (!is_array($price)) {
        return [
            "available" => null,
            "premium"   => false,
            "currency"  => null,
            "register"  => null,
            "renew"     => null,
            "transfer"  => null,
        ];
    }

    $isPremiumRaw = premium_get($price, ["IsPremium", "isPremium", "premium"]);
    $premium = in_array(strtolower((string) $isPremiumRaw), ["1", "yes", "true"], true) || (int) $isPremiumRaw === 1;

    $currency = premium_get($price, ["FeeCurrency", "currency", "Currency"]);
    $register = premium_num(premium_get($price, ["Fee4Registration", "fee4Registration", "registration"]));
    $renew    = premium_num(premium_get($price, ["Fee4Renewal", "fee4Renewal", "renewal"]));
    $transfer = premium_num(premium_get($price, ["Fee4Transfer", "fee4Transfer", "transfer"]));

    return [
        "available" => null,
        "premium"   => $premium,
        "currency"  => $currency ? strtoupper($currency) : null,
        "register"  => $register,
        "renew"     => $renew,
        "transfer"  => $transfer,
    ];
}

// ---- 数据源 2：Dynadot search（补充 / 兜底）---------------------------------
// 兼容旧版 api3.json 与新版 RESTful v1；返回统一结构或 null。
function premium_from_dynadot($domain)
{
    $key = getenv("DYNADOT_API_KEY");
    if (!$key) {
        return null;
    }

    // 优先尝试新版 RESTful v1（Bearer）
    $json = premium_http(
        "GET",
        "https://api.dynadot.com/restful/v1/domains/" . rawurlencode($domain) . "/search?show_price=true&currency=USD",
        ["headers" => ["Authorization: Bearer " . $key], "timeout" => 7]
    );
    // 回退旧版 api3.json（key 作为 query 参数）
    if (!$json) {
        $json = premium_http(
            "GET",
            "https://api.dynadot.com/api3.json?key=" . urlencode($key)
                . "&command=search&show_price=1&currency=USD&domain0=" . urlencode($domain),
            ["timeout" => 7]
        );
    }
    if (!$json) {
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
    if ($result === null && (premium_get($json, ["Available", "available"]) !== null)) {
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

// ---- 聚合：Netim 主源（价格最全）+ Dynadot 补充 -----------------------------
$netim = premium_from_netim($domain);
$dyna  = premium_from_dynadot($domain);

// 两源都不可用：明确返回未启用/无数据
if ($netim === null && $dyna === null) {
    premium_emit(json_encode([
        "code" => 200, "domain" => $domain, "premium" => false, "available" => null,
        "currency" => null, "register" => null, "renew" => null, "transfer" => null,
        "register_cny" => null, "renew_cny" => null, "transfer_cny" => null, "source" => "",
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $cacheDir, $cacheFile, 1800);
}

// 选源：优先采用「已判为溢价且带价格」的源；Netim 价格更全，作为首选。
$premium = false;
$available = null;
$currency = null;
$register = $renew = $transfer = null;
$source = "";

foreach ([["netim", $netim], ["dynadot", $dyna]] as $pair) {
    [$name, $d] = $pair;
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

// 若判为溢价但主选源缺某项价格，尝试用另一源补全
if ($premium) {
    foreach ([$netim, $dyna] as $d) {
        if ($d === null || !$d["premium"]) {
            continue;
        }
        if ($register === null && $d["register"] !== null) {
            $register = $d["register"];
            if ($currency === null) $currency = $d["currency"];
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
