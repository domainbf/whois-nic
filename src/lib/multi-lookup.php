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
 * 关键：DNS 必须**并行**。此前用 PHP 阻塞式 checkdnsrr 逐个查询 34 个域名，
 * 对不存在的子域每次都要等满解析器超时，累计远超函数时限 → 504 超时崩溃。
 * 现改用**并行 DNS-over-HTTPS(DoH)**（curl_multi 一次性并发），秒级返回，
 * 且能干净区分三态：
 *   - DoH 有 NS 应答(Status 0)  → 已委派 → registered（已注册）
 *   - DoH NXDOMAIN(Status 3)    → 域名不存在 → available（可注册）
 *   - DoH 其它(SERVFAIL/NODATA/错误) → 未定，转 SSL 复核
 * SSL 复核同样并行：443 TLS 握手成功 → registered（在用）；否则 unknown。
 */
function multiViaSslDns(array $domains)
{
    // 第一遍：并行 DoH（NS 记录）。返回 label => 'registered'|'available'|'undetermined'
    $doh = multiDohProbe($domains);

    $confirmed = [];    // label => state（已确定：registered / available）
    $undetermined = []; // label => domain（需 SSL 复核）
    foreach ($domains as $label => $domain) {
        $state = $doh[$label] ?? "undetermined";
        if ($state === "registered" || $state === "available") {
            $confirmed[$label] = $state;
        } else {
            $undetermined[$label] = $domain;
        }
    }

    // 第二遍：仅对未定域名并行 SSL/TLS 握手复核（捕获无 NS 但有在线 HTTPS 的情况）
    $sslOk = !empty($undetermined) ? multiSslProbe($undetermined) : [];

    $items = [];
    foreach ($domains as $label => $domain) {
        if (isset($confirmed[$label])) {
            $state = $confirmed[$label];
        } elseif (!empty($sslOk[$label])) {
            $state = "registered";
        } else {
            $state = "unknown";
        }
        $items[] = [
            "label" => $label,
            "domain" => $domain,
            "state" => $state,
        ];
    }
    return $items;
}

/**
 * 并行 DNS-over-HTTPS 探测（Cloudflare DoH JSON 接口）。
 * 对每个域名并发查询 NS 记录，解析 DoH 返回的 Status/Answer：
 *   - Status 0 且含 NS 应答 → registered
 *   - Status 3 (NXDOMAIN)   → available
 *   - 其它（2 SERVFAIL / NODATA / HTTP 错误 / 解析失败） → undetermined
 *
 * @return array label => 'registered'|'available'|'undetermined'
 */
function multiDohProbe(array $domains)
{
    $keyOf = function ($ch) {
        return is_object($ch) ? spl_object_id($ch) : (int) $ch;
    };

    $mh = curl_multi_init();
    $active = [];
    $out = [];

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
            CURLOPT_URL => "https://cloudflare-dns.com/dns-query?name=" . rawurlencode($host) . "&type=NS",
            CURLOPT_HTTPHEADER => ["accept: application/dns-json"],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_ENCODING => "",
            CURLOPT_NOSIGNAL => true,
        ]);
        curl_multi_add_handle($mh, $ch);
        $active[$keyOf($ch)] = ["label" => $label, "ch" => $ch];
        $out[$label] = "undetermined";
    }

    do {
        $mrc = curl_multi_exec($mh, $running);

        while ($info = curl_multi_info_read($mh)) {
            $ch = $info["handle"];
            $k = $keyOf($ch);
            $meta = $active[$k] ?? null;
            if ($meta) {
                $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $body = curl_multi_getcontent($ch);
                if ($code === 200 && $body) {
                    $json = json_decode($body, true);
                    if (is_array($json) && isset($json["Status"])) {
                        $status = (int) $json["Status"];
                        $hasNs = false;
                        if (!empty($json["Answer"]) && is_array($json["Answer"])) {
                            foreach ($json["Answer"] as $ans) {
                                if ((int) ($ans["type"] ?? 0) === 2) { // 2 = NS
                                    $hasNs = true;
                                    break;
                                }
                            }
                        }
                        if ($status === 0 && $hasNs) {
                            $out[$meta["label"]] = "registered";
                        } elseif ($status === 3) {
                            $out[$meta["label"]] = "available";
                        }
                        // 其它保持 undetermined
                    }
                }
                unset($active[$k]);
            }
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }

        // 防止 curl_multi_select 在无描述符时立即返回 -1 造成 CPU 空转；
        // 同时以 $mrc === CURLM_OK 作为循环护栏，避免出错时死循环。
        if ($running > 0 && curl_multi_select($mh, 1.0) === -1) {
            usleep(1000);
        }
    } while ($running > 0 && $mrc === CURLM_OK);

    curl_multi_close($mh);
    return $out;
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
        $mrc = curl_multi_exec($mh, $running);

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

        // 防止 curl_multi_select 在无描述符时立即返回 -1 造成 CPU 空转；
        // 同时以 $mrc === CURLM_OK 作为循环护栏，避免出错时死循环。
        if ($running > 0 && curl_multi_select($mh, 1.0) === -1) {
            usleep(1000);
        }
    } while ($running > 0 && $mrc === CURLM_OK);

    curl_multi_close($mh);
    return $ok;
}
