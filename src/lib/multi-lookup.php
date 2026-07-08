<?php

/**
 * 多域名批量查询（暗号 "0" 触发）。
 *
 * 固定前缀：26 个英文字母 a-z + nic/www/com/net/org/dns/api/pay，
 * 与用户输入的后缀拼接为 34 个域名，批量探测“已注册 / 可注册”状态。
 *
 * 性能关键：34 个域名共享同一个后缀（TLD），因此 RDAP 服务器只需解析一次，
 * 再用 curl_multi 并行发起 34 个请求，总耗时约等于单次 RDAP 查询。
 * 对没有 RDAP 接口的后缀，回退到 DNS(NS) 探测（有记录=已注册）。
 */

// 固定前缀集合（去重后 34 个）。
function multiDomainPrefixes()
{
    $letters = range("a", "z"); // a-z
    $extra = ["nic", "www", "com", "net", "org", "dns", "api", "pay"];
    // array_values + array_unique 保证去重且保持顺序
    return array_values(array_unique(array_merge($letters, $extra)));
}

/**
 * 将用户输入清洗为合法后缀（TLD），非法返回空串。
 * 允许多级后缀（如 com.cn），仅保留字母数字、点和连字符。
 */
function sanitizeSuffix($input)
{
    $suffix = strtolower(trim((string) $input));
    // 去掉可能的前导点、空白
    $suffix = trim($suffix, ". \t\n\r");
    // 仅保留合法字符
    $suffix = preg_replace("/[^a-z0-9.\-\x{4e00}-\x{9fa5}]/u", "", $suffix);
    return $suffix;
}

/**
 * 执行多域名批量查询。
 *
 * @param string $suffix 用户输入的后缀（如 com、ke、com.cn）
 * @return array{suffix:string, extension:string, source:string, items:array, elapsed:float}
 */
function multiDomainLookup($suffix)
{
    $start = microtime(true);
    $suffix = sanitizeSuffix($suffix);

    $result = [
        "suffix" => $suffix,
        "extension" => $suffix,
        "source" => "none",
        "items" => [],
        "elapsed" => 0.0,
    ];

    if ($suffix === "" || strpos($suffix, ".") === 0) {
        $result["elapsed"] = microtime(true) - $start;
        return $result;
    }

    $prefixes = multiDomainPrefixes();

    // 1) 解析后缀 → extension（复用 Lookup 的域名解析，dataSource 为空则不发起任何查询）
    $extension = $suffix;
    try {
        $probe = new Lookup("a." . $suffix, []);
        if (!empty($probe->extension)) {
            $extension = $probe->extension;
        }
    } catch (Throwable $t) {
        // 后缀无法解析：视为无效，返回空列表
        $result["elapsed"] = microtime(true) - $start;
        return $result;
    }
    $result["extension"] = $extension;

    // 2) 组装 34 个域名
    $domains = [];
    foreach ($prefixes as $prefix) {
        $domains[$prefix] = $prefix . "." . $suffix;
    }

    // 3) 解析 RDAP 服务器（只解析一次，同后缀共用）
    $serverBase = "";
    try {
        $rdap = new RDAP("a." . $suffix, $extension, "");
        $serverBase = $rdap->getServerBase();
    } catch (Throwable $t) {
        $serverBase = "";
    }

    if ($serverBase !== "") {
        $result["source"] = "rdap";
        $result["items"] = multiViaRdap($serverBase, $domains);
    } else {
        // 无 RDAP 接口：DNS 兜底
        $result["source"] = "dns";
        $result["items"] = multiViaDns($domains);
    }

    $result["elapsed"] = microtime(true) - $start;
    return $result;
}

/**
 * RDAP 并行探测：HTTP 200=已注册，404=可注册，其它=未知。
 */
function multiViaRdap($serverBase, array $domains)
{
    $items = [];
    $mh = curl_multi_init();
    $handles = [];

    foreach ($domains as $label => $domain) {
        $ascii = $domain;
        if (function_exists("idn_to_ascii")) {
            $converted = idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            if ($converted) {
                $ascii = $converted;
            }
        }
        $url = rtrim($serverBase, "/") . "/domain/" . rawurlencode($ascii);
        $ch = RDAP::buildHandleForUrl($url);
        curl_multi_add_handle($mh, $ch);
        $handles[$label] = ["ch" => $ch, "domain" => $domain];
    }

    // 并行执行所有请求
    do {
        $status = curl_multi_exec($mh, $running);
        if ($running) {
            curl_multi_select($mh, 1.0);
        }
    } while ($running && $status === CURLM_OK);

    foreach ($handles as $label => $h) {
        $ch = $h["ch"];
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $state = "unknown";
        if ($code === 200) {
            $state = "registered";
        } elseif ($code === 404) {
            $state = "available";
        }

        $items[] = [
            "label" => $label,
            "domain" => $h["domain"],
            "state" => $state,
        ];

        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }

    curl_multi_close($mh);
    return $items;
}

/**
 * DNS 兜底探测：存在 NS 记录=已注册，否则未知（DNS 无记录不足以断定可注册）。
 */
function multiViaDns(array $domains)
{
    $items = [];
    foreach ($domains as $label => $domain) {
        $state = domainHasDnsRecords($domain) ? "registered" : "unknown";
        $items[] = [
            "label" => $label,
            "domain" => $domain,
            "state" => $state,
        ];
    }
    return $items;
}
