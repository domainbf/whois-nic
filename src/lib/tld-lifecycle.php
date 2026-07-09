<?php

/**
 * 域名释放（可重新注册）时间预测
 * ------------------------------------------------------------------
 * 依据 src/data/tld-lifecycle.php 中的后缀生命周期数据，结合域名到期日
 * 与真实的 EPP 状态码，推算域名进入公开可注册状态的大致时间。
 */

/** 取某后缀的生命周期配置（缺省字段回退到 _default 的 gTLD 标准）。 */
function tld_lifecycle_config(string $tld): array
{
    static $db = null;
    if ($db === null) {
        $db = require __DIR__ . "/../data/tld-lifecycle.php";
    }
    $tld = strtolower(ltrim(trim($tld), "."));
    $default = $db["_default"];
    $conf = $db[$tld] ?? $default;
    // 用 _default 补齐缺省的天数字段，避免未定义键
    return $conf + $default;
}

/** 从完整域名取最后一级后缀（生命周期由实际注册局决定，末级标签即可）。 */
function tld_from_domain(string $domain): string
{
    $domain = strtolower(rtrim(trim($domain), "."));
    $pos = strrpos($domain, ".");
    return $pos === false ? $domain : substr($domain, $pos + 1);
}

/**
 * 生成释放时间预测。
 *
 * @param string      $domain        完整域名（用于取后缀）
 * @param string|null $expirationISO 到期日（ISO8601 / 可被 strtotime 解析）
 * @param array       $statusCodes   EPP 状态码数组（如 ["redemptionPeriod", ...]），用于校准当前阶段
 * @return array|null                无到期日时返回 null
 */
function domain_release_forecast(string $domain, ?string $expirationISO, array $statusCodes = []): ?array
{
    if (!$expirationISO) {
        return null;
    }
    $exp = strtotime($expirationISO);
    if ($exp === false) {
        return null;
    }

    $tld = tld_from_domain($domain);
    $conf = tld_lifecycle_config($tld);
    $registry = $conf["registry"] ?? null;

    // 无固定删除周期的后缀：只返回不可预测标记
    if (isset($conf["predictable"]) && $conf["predictable"] === false) {
        return [
            "predictable" => false,
            "tld"         => $tld,
            "registry"    => $registry,
        ];
    }

    $day = 86400;
    $renewDays = (int) ($conf["renewGrace"] ?? 0);
    $redeemDays = (int) ($conf["redemption"] ?? 0);
    $pendingDays = (int) ($conf["pendingDelete"] ?? 0);

    // 各阶段边界时间戳
    $renewEnd = $exp + $renewDays * $day;
    $redeemEnd = $renewEnd + $redeemDays * $day;
    $pendingEnd = $redeemEnd + $pendingDays * $day;
    $releaseTs = $pendingEnd;

    // 构建阶段列表（跳过天数为 0 的阶段，但保留起止用于计算）
    $phases = [];
    $phases[] = ["key" => "renewGrace", "start" => $exp, "end" => $renewEnd, "days" => $renewDays];
    $phases[] = ["key" => "redemption", "start" => $renewEnd, "end" => $redeemEnd, "days" => $redeemDays];
    $phases[] = ["key" => "pendingDelete", "start" => $redeemEnd, "end" => $pendingEnd, "days" => $pendingDays];

    $now = time();

    // 1) 优先用真实 EPP 状态码判定当前阶段（比纯日期推算更准，规避注册商差异）
    $lc = array_map(fn($s) => strtolower((string) $s), $statusCodes);
    $has = function (array $needles) use ($lc): bool {
        foreach ($lc as $s) {
            foreach ($needles as $n) {
                if (strpos($s, strtolower($n)) !== false) {
                    return true;
                }
            }
        }
        return false;
    };

    $currentPhase = null;
    if ($has(["pendingdelete"])) {
        $currentPhase = "pendingDelete";
    } elseif ($has(["redemption", "redemptionperiod"])) {
        $currentPhase = "redemption";
    } elseif ($has(["autorenew", "renewperiod", "graceperiod", "addperiod"])) {
        $currentPhase = "renewGrace";
    }

    // 2) 否则按当前时间落在哪个阶段推算
    if ($currentPhase === null) {
        if ($now < $exp) {
            $currentPhase = "active"; // 尚未到期
        } elseif ($now < $renewEnd && $renewDays > 0) {
            $currentPhase = "renewGrace";
        } elseif ($now < $redeemEnd && $redeemDays > 0) {
            $currentPhase = "redemption";
        } elseif ($now < $pendingEnd && $pendingDays > 0) {
            $currentPhase = "pendingDelete";
        } else {
            $currentPhase = "released";
        }
    }

    $daysUntilRelease = (int) ceil(($releaseTs - $now) / $day);
    $released = $now >= $releaseTs;

    return [
        "predictable"      => true,
        "tld"              => $tld,
        "registry"         => $registry,
        "expiration"       => $exp,
        "phases"           => $phases,
        "renewEnd"         => $renewEnd,
        "redeemEnd"        => $redeemEnd,
        "pendingEnd"       => $pendingEnd,
        "releaseTs"        => $releaseTs,
        "releaseDate"      => date("Y-m-d", $releaseTs),
        "daysUntilRelease" => $daysUntilRelease,
        "released"         => $released,
        "currentPhase"     => $currentPhase,
        "totalDays"        => $renewDays + $redeemDays + $pendingDays,
    ];
}
