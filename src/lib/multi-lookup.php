<?php

/**
 * 多域名批量查询（暗号 "0" 触发）。
 *
 * 固定前缀：26 个英文字母 a-z + nic/www/com/net/org/dns/api/pay，
 * 与用户输入的后缀拼接为 34 个域名，批量探测状态。
 *
 * 探测方式：**只用 SSL 握手 + DNS**，不碰 RDAP/WHOIS。
 * 原因：批量并发打 RDAP/WHOIS 会触发注册局限流（如 .ke 大量 429），
 * 结果不准。SSL/DNS 走的是公共解析器与目标主机，不受注册局限流约束：
 *   - DNS 有 NS/A 记录  → 域名已委派/在解析 → 已注册
 *   - SSL(443) 握手成功 → 有在线 HTTPS 服务   → 已注册（在用）
 *   - 两者都无法确认    → 标记“未知”，由用户点击该域名再走完整查询
 * 这样批量结果快且不会被限流；“未知”项交给用户按需精确核实。
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

    // 1) 校验后缀可解析（复用 Lookup 的域名解析，dataSource 为空则不发起任何查询）
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

    // 3) SSL + DNS 探测（不碰 RDAP/WHOIS，避免注册局限流）
    $result["source"] = "ssl+dns";
    $result["items"] = multiViaSslDns($domains);

    $result["elapsed"] = microtime(true) - $start;
    return $result;
}

/**
 * SSL + DNS 批量探测（不碰 RDAP/WHOIS，规避注册局限流）。
 *
 * 判定规则（任一命中即“已注册/在用”）：
 *   1) SSL：443 端口 TLS 握手成功 → 有在线 HTTPS 服务
 *   2) DNS：存在 NS 或 A 记录     → 域名已委派/在解析
 * 两者都无法确认 → “未知”（由用户点击该域名再走完整查询）。
 *
 * SSL 探测用 curl_multi 全部并行（面向各目标主机，非注册局，无限流风险）；
 * 未被 SSL 确认的域名再做一次 DNS 记录检查作为补充信号。
 */
function multiViaSslDns(array $domains)
{
    // 第一遍：DNS NS/A 记录检查（最可靠的注册信号，NXDOMAIN 立即返回，极快）。
    // 绝大多数已注册域名都有 NS 委派，这一步即可确认。
    $confirmed = [];   // label => true（已确认注册）
    $undetermined = []; // label => domain（DNS 未命中，进入 SSL 复核）
    foreach ($domains as $label => $domain) {
        if (domainHasDnsRecords($domain)) {
            $confirmed[$label] = true;
        } else {
            $undetermined[$label] = $domain;
        }
    }

    // 第二遍：仅对 DNS 未命中的少数域名并行做 SSL/TLS 握手复核（捕获无 NS 但
    // 有在线 HTTPS 服务的边缘情况）。目标是各主机，非注册局，无限流风险。
    $sslOk = !empty($undetermined) ? multiSslProbe($undetermined) : [];

    $items = [];
    foreach ($domains as $label => $domain) {
        $registered = !empty($confirmed[$label]) || !empty($sslOk[$label]);
        $items[] = [
            "label" => $label,
            "domain" => $domain,
            "state" => $registered ? "registered" : "unknown",
        ];
    }
    return $items;
}

/**
 * 并行 SSL/TLS 握手探测：对每个域名尝试与其 443 端口建立 TLS 连接。
 * 握手成功即视为“在用”。使用 CURLOPT_CONNECT_ONLY 只做连接（含 TLS），
 * 不发送 HTTP 请求；不校验证书有效性（只关心对端是否响应 TLS）。
 *
 * @return array label => bool（true 表示握手成功）
 */
function multiSslProbe(array $domains)
{
    $keyOf = function ($ch) {
        return is_object($ch) ? spl_object_id($ch) : (int) $ch;
    };

    $mh = curl_multi_init();
    $active = [];
    $ok = [];

    foreach ($domains as $label => $domain) {
        $host = $domain;
        if (function_exists("idn_to_ascii")) {
            $converted = idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            if ($converted) {
                $host = $converted;
            }
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "https://" . $host . "/",
            // 只建立连接（含 TLS 握手），不发送/接收 HTTP 数据 —— 最轻量的“在用”探测
            CURLOPT_CONNECT_ONLY => true,
            // 仅作为 DNS 未命中的少量复核，收紧超时避免拖慢整体
            CURLOPT_TIMEOUT => 4,
            CURLOPT_CONNECTTIMEOUT => 3,
            // 只关心对端是否能完成 TLS 握手，不校验证书是否可信/匹配
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_NOSIGNAL => true,
        ]);
        curl_multi_add_handle($mh, $ch);
        $active[$keyOf($ch)] = ["label" => $label, "ch" => $ch];
        $ok[$label] = false;
    }

    // 全部并行执行
    do {
        curl_multi_exec($mh, $running);

        while ($info = curl_multi_info_read($mh)) {
            $ch = $info["handle"];
            $k = $keyOf($ch);
            $meta = $active[$k] ?? null;
            if ($meta) {
                // CURLE_OK(0) 表示 TCP+TLS 连接已建立
                $ok[$meta["label"]] = ((int) $info["result"] === 0);
                unset($active[$k]);
            }
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }

        if ($running) {
            curl_multi_select($mh, 1.0);
        }
    } while ($running);

    curl_multi_close($mh);
    return $ok;
}
