<?php

/**
 * 溢价域名检测代理（逐域名）
 *
 * 与 price.php（后缀级通用价）不同，本接口检测「具体域名」本身是否为
 * 溢价（premium）域名，并返回其溢价价格。数据源：
 *   - 主源 Dynadot（search API，免费、覆盖广、响应快）
 *   - 补充源 Netim（代理 REST API，用于交叉验证 / 补全 Dynadot 缺失的价格）
 * 采用 Dynadot 优先策略：Dynadot 命中溢价即以其为准，未命中或缺价时再看 Netim。
 *
 * 价格统一附带人民币估算（price_cny），汇率每日缓存一次。
 * 密钥仅在服务端读取（DYNADOT_API_KEY / NETIM_API_LOGIN / NETIM_API_SECRET），
 * 任一数据源缺凭证时自动跳过，优雅降级，绝不因单源失败而报错。
 *
 * 请求: GET /premium?domain=example.com
 * 响应: {
 *   code: 200,
 *   domain: "example.com",
 *   premium: true|false,        // 是否为溢价域名
 *   available: true|false|null, // 是否可注册（null=未知）
 *   price: 2999.99|null,        // 溢价/注册价（原币种）
 *   currency: "USD"|null,
 *   price_cny: 21599.93|null,   // 人民币估算
 *   source: "dynadot"|"netim"|"dynadot+netim"|""
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

// ---- 通用 HTTP GET -----------------------------------------------------------
function premium_http_get($url, $headers = [], $timeout = 6)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => "Mozilla/5.0 (compatible; WhoisNic/1.0)",
        CURLOPT_HTTPHEADER     => array_merge(["Accept: application/json"], $headers),
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

// ---- 通用 HTTP（自定义方法，用于 Netim 会话鉴权 POST）------------------------
function premium_http_request($method, $url, $headers = [], $body = null, $timeout = 6)
{
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => "Mozilla/5.0 (compatible; WhoisNic/1.0)",
        CURLOPT_HTTPHEADER     => array_merge(["Accept: application/json", "Content-Type: application/json"], $headers),
    ];
    if ($body !== null) {
        $opts[CURLOPT_POSTFIELDS] = is_string($body) ? $body : json_encode($body);
    }
    curl_setopt_array($ch, $opts);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($resp === false || $code < 200 || $code >= 300) {
        return null;
    }
    $json = json_decode($resp, true);
    return is_array($json) ? $json : null;
}

// ---- 从任意字符串中提取「金额 + 币种」-----------------------------------------
// 兼容 Dynadot 多种价格表述，如 "2999.99 in USD" / "$2,999.99 USD" / "USD 2999.99"。
function premium_parse_price($str)
{
    if ($str === null || $str === "") {
        return [null, null];
    }
    $s = (string) $str;

    // 币种：优先三字母 ISO 代码，其次常见货币符号
    $currency = null;
    if (preg_match('/\b([A-Z]{3})\b/', $s, $m)) {
        $currency = $m[1];
    } elseif (strpos($s, "$") !== false) {
        $currency = "USD";
    } elseif (strpos($s, "€") !== false) {
        $currency = "EUR";
    } elseif (strpos($s, "£") !== false) {
        $currency = "GBP";
    }

    // 金额：抓取首个数字（允许千分位逗号与小数）
    $price = null;
    if (preg_match('/([0-9][0-9,]*(?:\.[0-9]+)?)/', $s, $m)) {
        $num = (float) str_replace(",", "", $m[1]);
        if ($num > 0) {
            $price = $num;
        }
    }
    return [$price, $currency];
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
            $fx = premium_http_get("https://open.er-api.com/v6/latest/USD", [], 5);
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

// ---- 数据源 1：Dynadot search（主源）----------------------------------------
// 返回: ["available"=>bool|null, "premium"=>bool, "price"=>float|null, "currency"=>string|null] 或 null
function premium_from_dynadot($domain)
{
    $key = getenv("DYNADOT_API_KEY");
    if (!$key) {
        return null;
    }
    $url = "https://api.dynadot.com/api3.json?key=" . urlencode($key)
        . "&command=search&show_price=1&currency=USD&domain0=" . urlencode($domain);
    $json = premium_http_get($url, [], 6);
    if (!$json) {
        return null;
    }

    // 兼容多种响应外层结构
    $result = null;
    if (!empty($json["SearchResponse"]["SearchResults"][0])) {
        $result = $json["SearchResponse"]["SearchResults"][0];
    } elseif (!empty($json["SearchResults"][0])) {
        $result = $json["SearchResults"][0];
    } elseif (isset($json["Available"]) || isset($json["available"])) {
        $result = $json;
    }
    if (!is_array($result)) {
        return null;
    }

    // 取值助手：忽略键名大小写差异
    $get = function ($keys) use ($result) {
        foreach ((array) $keys as $k) {
            foreach ($result as $rk => $rv) {
                if (strcasecmp($rk, $k) === 0) {
                    return $rv;
                }
            }
        }
        return null;
    };

    $availRaw = strtolower((string) $get(["Available", "available"]));
    $available = $availRaw === "" ? null : in_array($availRaw, ["yes", "true", "1"], true);

    $premRaw = strtolower((string) $get(["IsPremium", "Premium", "premium", "is_premium"]));
    $premium = in_array($premRaw, ["yes", "true", "1"], true);

    // 价格：可能是结构化字段，也可能是描述性字符串
    [$price, $currency] = premium_parse_price($get(["Price", "price", "PremiumPrice", "premium_price"]));
    if ($currency === null) {
        $currency = "USD"; // 已显式请求 currency=USD
    }

    // 溢价的强信号：价格明显高于普通注册价（阈值 100 单位货币）
    if (!$premium && $price !== null && $price >= 100) {
        $premium = true;
    }

    return [
        "available" => $available,
        "premium"   => $premium,
        "price"     => $price,
        "currency"  => $price !== null ? $currency : null,
    ];
}

// ---- 数据源 2：Netim REST（补充/交叉验证）-----------------------------------
// 需要代理账户凭证；先换取会话令牌，再查询域名。任何环节失败均返回 null。
function premium_from_netim($domain)
{
    $login = getenv("NETIM_API_LOGIN");
    $secret = getenv("NETIM_API_SECRET");
    if (!$login || !$secret) {
        return null;
    }

    $base = "https://rest.netim.com/1.0";

    // 1) 建立会话，获取令牌
    $session = premium_http_request("POST", $base . "/session/", [], [
        "login"    => $login,
        "secret"   => $secret,
        "language" => "EN",
    ], 6);
    if (!$session) {
        return null;
    }
    // 令牌字段名兼容多种可能
    $token = $session["id"] ?? ($session["sessionId"] ?? ($session["session_id"] ?? ($session["token"] ?? null)));
    if (!$token) {
        return null;
    }
    $auth = ["Authorization: Bearer " . $token];

    // 2) 查询域名可注册性 / 溢价
    $check = premium_http_get($base . "/domain/" . urlencode($domain) . "/check/", $auth, 6);

    // 3) 主动关闭会话（尽力而为，忽略结果）
    premium_http_request("DELETE", $base . "/session/", $auth, null, 4);

    if (!$check) {
        return null;
    }
    // check 可能是对象或数组包裹
    $row = $check;
    if (isset($check[0]) && is_array($check[0])) {
        $row = $check[0];
    }
    if (!is_array($row)) {
        return null;
    }

    $get = function ($keys) use ($row) {
        foreach ((array) $keys as $k) {
            foreach ($row as $rk => $rv) {
                if (strcasecmp($rk, $k) === 0) {
                    return $rv;
                }
            }
        }
        return null;
    };

    $resultRaw = strtolower((string) $get(["result", "Result", "status"]));
    $available = $resultRaw === "" ? null : (strpos($resultRaw, "available") !== false && strpos($resultRaw, "not") === false);

    $premRaw = strtolower((string) $get(["IsPremium", "premium", "isPremium"]));
    $premium = in_array($premRaw, ["yes", "true", "1"], true);

    // Netim 溢价价格字段候选
    [$price, $currency] = premium_parse_price($get(["fee", "Fee", "premiumFee", "price", "Price", "createFee"]));
    if ($price !== null && $currency === null) {
        $currency = "EUR"; // Netim 默认以欧元计价
    }
    if (!$premium && $price !== null && $price >= 100) {
        $premium = true;
    }

    return [
        "available" => $available,
        "premium"   => $premium,
        "price"     => $price,
        "currency"  => $price !== null ? $currency : null,
    ];
}

// ---- 聚合：Dynadot 优先，Netim 补充 -----------------------------------------
$dyn = premium_from_dynadot($domain);
$net = premium_from_netim($domain);

$sources = [];
if ($dyn !== null) {
    $sources["dynadot"] = true;
}
if ($net !== null) {
    $sources["netim"] = true;
}

// 两源都不可用（无凭证或全部失败）：明确返回未启用/无数据，前端据此不展示徽章
if ($dyn === null && $net === null) {
    $payload = json_encode([
        "code"      => 200,
        "domain"    => $domain,
        "premium"   => false,
        "available" => null,
        "price"     => null,
        "currency"  => null,
        "price_cny" => null,
        "source"    => "",
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    // 无数据也短暂缓存，避免反复打空请求
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0777, true);
    }
    @file_put_contents($cacheFile, $payload, LOCK_EX);
    header("X-Premium-Cache: MISS");
    header("Cache-Control: public, max-age=1800");
    echo $payload;
    exit;
}

// 溢价：Dynadot 命中即以其为准；否则看 Netim
$premium = false;
$price = null;
$currency = null;
$available = null;
$pickedSource = "";

foreach ([["dynadot", $dyn], ["netim", $net]] as $pair) {
    [$name, $data] = $pair;
    if ($data === null) {
        continue;
    }
    if ($available === null && $data["available"] !== null) {
        $available = $data["available"];
    }
    // 优先采用「已标记溢价且带价格」的结果；Dynadot 因排在前面而优先
    if ($data["premium"] && $data["price"] !== null && !$premium) {
        $premium = true;
        $price = $data["price"];
        $currency = $data["currency"];
        $pickedSource = $name;
    } elseif ($data["premium"] && !$premium && $price === null) {
        // 标记溢价但无价格：先记录溢价状态，价格留待后续源补全
        $premium = true;
        $pickedSource = $name;
    }
}

// 若判为溢价但仍无价格，尝试从任一源补一个价格
if ($premium && $price === null) {
    foreach ([$dyn, $net] as $data) {
        if ($data !== null && $data["price"] !== null) {
            $price = $data["price"];
            $currency = $data["currency"];
            break;
        }
    }
}

$priceCny = premium_to_cny($price, $currency);

$payload = json_encode([
    "code"      => 200,
    "domain"    => $domain,
    "premium"   => $premium,
    "available" => $available,
    "price"     => $price,
    "currency"  => $currency,
    "price_cny" => $priceCny,
    "source"    => $pickedSource ?: (implode("+", array_keys($sources))),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0777, true);
}
@file_put_contents($cacheFile, $payload, LOCK_EX);
header("X-Premium-Cache: MISS");
header("Cache-Control: public, max-age=3600");
echo $payload;
