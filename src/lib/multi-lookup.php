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
 * RDAP 探测：HTTP 200=已注册，404=可注册，其它=未知/待重试。
 *
 * 关键：注册局（如 .ke）对短时间大量请求会返回 429 限流，一次性并发 34
 * 个请求会导致后半段大量被拒 → "未知"。实测仅降并发仍会大面积 429，
 * 因此采用「**限流并发 + 多轮退避重试**」：
 *   1) 每轮以较低并发（4）处理待办域名；
 *   2) 把 429 / 连接失败（瞬时故障）的域名收集到下一轮；
 *   3) 轮次间递增退避（0.6s、1.2s…），最多 3 轮；
 *   4) 仍未判定的域名再用 DNS(NS/A) 兜底。
 * 这样能在注册局限流下把"未知"降到最少，显著提升准确性。
 */
function multiViaRdap($serverBase, array $domains)
{
    $serverBase = rtrim($serverBase, "/");
    $maxConcurrent = 4;   // 每轮并发上限（较低以规避限流）
    $maxRounds = 3;       // 最多重试轮数

    $labels = array_keys($domains);
    $results = [];        // label => item
    $pending = $labels;   // 本轮待查询的 label 列表

    for ($round = 0; $round < $maxRounds && !empty($pending); $round++) {
        if ($round > 0) {
            // 轮次间退避，给注册局限流窗口恢复时间
            usleep(600000 * $round); // 0.6s, 1.2s
        }

        $roundDomains = [];
        foreach ($pending as $label) {
            $roundDomains[$label] = $domains[$label];
        }
        $roundResult = multiRdapRound($serverBase, $roundDomains, $maxConcurrent);

        // 收集本轮结果，把瞬时故障（retry=true）留到下一轮
        $pending = [];
        foreach ($roundResult as $label => $r) {
            if (!empty($r["retry"])) {
                $pending[] = $label;
            } else {
                $results[$label] = [
                    "label" => $label,
                    "domain" => $domains[$label],
                    "state" => $r["state"],
                ];
            }
        }
    }

    // 多轮后仍未判定的域名：标记为未知，交由 DNS 兜底
    foreach ($pending as $label) {
        $results[$label] = [
            "label" => $label,
            "domain" => $domains[$label],
            "state" => "unknown",
        ];
    }

    // DNS 兜底：对 RDAP 未能判定的域名，用 NS/A 记录再判一次（有记录=已注册）
    foreach ($results as $label => $r) {
        if ($r["state"] === "unknown" && domainHasDnsRecords($r["domain"])) {
            $results[$label]["state"] = "registered";
        }
    }

    // 按原始前缀顺序输出
    $ordered = [];
    foreach ($labels as $label) {
        if (isset($results[$label])) {
            $ordered[] = $results[$label];
        }
    }
    return $ordered;
}

/**
 * 单轮 RDAP 限流并发查询。
 * 返回 label => ['state'=>..., 'retry'=>bool]，retry=true 表示瞬时故障需重试。
 *   - HTTP 200 → registered
 *   - HTTP 404 → available
 *   - HTTP 429 / 连接失败(code 0) → retry=true
 *   - 其它 → unknown
 */
function multiRdapRound($serverBase, array $domains, $maxConcurrent)
{
    $keyOf = function ($ch) {
        return is_object($ch) ? spl_object_id($ch) : (int) $ch;
    };

    $queue = array_keys($domains);
    $active = [];
    $out = [];

    $mh = curl_multi_init();

    $addNext = function () use (&$queue, &$active, $mh, $domains, $serverBase, $keyOf) {
        if (empty($queue)) {
            return;
        }
        $label = array_shift($queue);
        $domain = $domains[$label];

        $ascii = $domain;
        if (function_exists("idn_to_ascii")) {
            $converted = idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            if ($converted) {
                $ascii = $converted;
            }
        }
        $url = $serverBase . "/domain/" . rawurlencode($ascii);
        $ch = RDAP::buildHandleForUrl($url);
        curl_multi_add_handle($mh, $ch);
        $active[$keyOf($ch)] = ["label" => $label, "domain" => $domain];
    };

    for ($i = 0; $i < $maxConcurrent; $i++) {
        $addNext();
    }

    do {
        curl_multi_exec($mh, $running);

        while ($info = curl_multi_info_read($mh)) {
            $ch = $info["handle"];
            $k = $keyOf($ch);
            $meta = $active[$k] ?? null;
            if ($meta) {
                $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $entry = ["state" => "unknown", "retry" => false];
                if ($code === 200) {
                    $entry["state"] = "registered";
                } elseif ($code === 404) {
                    $entry["state"] = "available";
                } elseif ($code === 429 || $code === 0 || ($code >= 500 && $code <= 599)) {
                    // 限流 / 连接失败 / 服务端错误：瞬时故障，下一轮重试
                    $entry["retry"] = true;
                }
                $out[$meta["label"]] = $entry;
                unset($active[$k]);
            }
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
            $addNext();
        }

        if ($running || !empty($queue) || !empty($active)) {
            curl_multi_select($mh, 1.0);
        }
    } while ($running || !empty($queue) || !empty($active));

    curl_multi_close($mh);
    return $out;
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
