<?php

/**
 * TLD / ccTLD 域名生命周期数据库
 * ------------------------------------------------------------------
 * 用于预测一个已过期域名"释放、可重新注册"的大致时间。
 *
 * 每个后缀的生命周期由到期日之后的几个阶段组成（单位：天）：
 *   - renewGrace    自动续费 / 续费宽限期：注册人仍可按常规价格续费
 *   - redemption    赎回期（RGP）：域名被挂起，仅能通过高价赎回恢复
 *   - pendingDelete 待删除期：不可恢复，等待注册局删除
 *   之后 => 释放（released），域名进入公开可注册状态
 *
 * 预计可注册日 = 到期日 + renewGrace + redemption + pendingDelete
 *
 * 数据来源：ICANN gTLD 生命周期规范、各注册局公开政策（Verisign / PIR /
 * Identity Digital / CNNIC / Nominet / EURid / SIDN / AFNIC / CIRA 等）。
 * 各注册商实际执行存在差异，故结果为"估算"，UI 中明确标注。
 *
 * 说明：
 *   - registry     注册局名称（展示用）
 *   - predictable  = false 表示该后缀无固定的、基于到期日的删除周期
 *                    （如 .de/.tk 等），此时只展示阶段说明、不给出确切日期。
 *   - 未列出的后缀回退到 _default（ICANN 通用 gTLD：45 / 30 / 5）。
 */

return [
    // ICANN 通用 gTLD 标准生命周期（大多数 gTLD 与新 gTLD 适用）
    "_default" => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "type" => "gtld"],

    // ---- 主流 gTLD ----
    "com"    => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Verisign"],
    "net"    => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Verisign"],
    "org"    => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "PIR"],
    "info"   => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Identity Digital"],
    "biz"    => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "GoDaddy Registry"],
    "mobi"   => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Identity Digital"],
    "pro"    => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Identity Digital"],
    "name"   => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Verisign"],
    "asia"   => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "DotAsia"],

    // ---- 常见新 gTLD（Identity Digital / Radix / GoDaddy 等，多为 45/30/5）----
    "xyz"    => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "XYZ.com"],
    "top"    => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Jiangsu Bangning"],
    "site"   => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Radix"],
    "online" => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Radix"],
    "store"  => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Radix"],
    "tech"   => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Radix"],
    "shop"   => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "GMO Registry"],
    "app"    => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Google Registry"],
    "dev"    => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Google Registry"],
    "vip"    => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Identity Digital"],
    "club"   => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Identity Digital"],
    "icu"    => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "ShortDot"],
    "live"   => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Identity Digital"],
    "cloud"  => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Aruba"],
    "pw"     => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Identity Digital"],

    // ---- Verisign 运营的 ccTLD（沿用 gTLD 周期）----
    "tv"     => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Verisign (Tuvalu)"],
    "cc"     => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Verisign"],

    // ---- 主流 ccTLD（各自政策）----
    "cn"     => ["renewGrace" => 30, "redemption" => 30, "pendingDelete" => 5, "registry" => "CNNIC"],
    "co"     => ["renewGrace" => 15, "redemption" => 30, "pendingDelete" => 5, "registry" => "GoDaddy Registry (.CO)"],
    "io"     => ["renewGrace" => 30, "redemption" => 30, "pendingDelete" => 5, "registry" => "Identity Digital"],
    "me"     => ["renewGrace" => 30, "redemption" => 30, "pendingDelete" => 5, "registry" => "doMEn"],
    "us"     => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "GoDaddy Registry"],
    "in"     => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "NIXI"],
    "ca"     => ["renewGrace" => 40, "redemption" => 30, "pendingDelete" => 5, "registry" => "CIRA"],
    "eu"     => ["renewGrace" => 0,  "redemption" => 40, "pendingDelete" => 0, "registry" => "EURid"],       // 到期后 40 天隔离期
    "nl"     => ["renewGrace" => 0,  "redemption" => 40, "pendingDelete" => 0, "registry" => "SIDN"],        // 40 天隔离期
    "fr"     => ["renewGrace" => 30, "redemption" => 30, "pendingDelete" => 0, "registry" => "AFNIC"],
    "ru"     => ["renewGrace" => 30, "redemption" => 0,  "pendingDelete" => 0, "registry" => "CCTLD.ru"],    // 30 天优先续费期
    "au"     => ["renewGrace" => 30, "redemption" => 0,  "pendingDelete" => 0, "registry" => "auDA"],
    "nz"     => ["renewGrace" => 90, "redemption" => 0,  "pendingDelete" => 0, "registry" => "InternetNZ"],  // 约 90 天暂停后释放
    "br"     => ["renewGrace" => 30, "redemption" => 30, "pendingDelete" => 0, "registry" => "NIC.br"],
    "es"     => ["renewGrace" => 0,  "redemption" => 0,  "pendingDelete" => 0, "registry" => "Red.es", "predictable" => false],
    "jp"     => ["renewGrace" => 30, "redemption" => 0,  "pendingDelete" => 0, "registry" => "JPRS"],        // 到期次月月底删除（估算）
    "kr"     => ["renewGrace" => 30, "redemption" => 0,  "pendingDelete" => 0, "registry" => "KISA"],

    // ---- 无固定到期删除周期 / 政策特殊，仅展示阶段说明 ----
    "uk"     => ["renewGrace" => 90, "redemption" => 0,  "pendingDelete" => 2, "registry" => "Nominet"],     // 约 90 天后取消并释放
    "de"     => ["registry" => "DENIC", "predictable" => false],   // DENIC 无标准赎回期，删除后进入 transit
    "tk"     => ["registry" => "Freenom", "predictable" => false],
    "ml"     => ["registry" => "Freenom", "predictable" => false],
    "ga"     => ["registry" => "Freenom", "predictable" => false],
    "cf"     => ["registry" => "Freenom", "predictable" => false],
    "gq"     => ["registry" => "Freenom", "predictable" => false],
];
