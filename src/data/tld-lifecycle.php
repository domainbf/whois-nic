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
 * ⚑ 数据可靠性说明
 *   gTLD（com/net/org 及全部 1000+ 新 gTLD）的删除周期由 ICANN 统一强制规定
 *   （45 天自动续费宽限 + 30 天 RGP 赎回 + 5 天 pendingDelete = 80 天），
 *   因此对所有 gTLD 都高度准确；未显式列出的 gTLD 自动回退 _default 即为该标准。
 *   ccTLD 由各国注册局自行制定，差异大，本表依据各注册局公开政策整理，标注
 *   来源与置信度；无确定性删除周期者（predictable=false）只展示阶段说明、不给日期。
 *
 * 字段说明：
 *   - registry     注册局名称（展示用）
 *   - confidence   "high"=依据 ICANN/注册局明确政策；"est"=依据公开资料估算
 *   - predictable  = false 表示无固定的、基于到期日的删除周期（如 .de/.es/.at），
 *                    此时只展示阶段说明、不给出确切日期。
 *   - 未列出的后缀回退到 _default（ICANN 通用 gTLD：45 / 30 / 5）。
 *
 * 数据来源：ICANN gTLD Lifecycle / EPP RGP（RFC 3915）、Verisign、PIR、
 *   Identity Digital、Radix、Google Registry、GMO、ShortDot、CentralNic、
 *   CNNIC、Nominet、EURid、SIDN、AFNIC、DNS Belgium、IIS、CIRA、auDA、
 *   InternetNZ、JPRS、KISA、TWNIC、HKIRC、SGNIC、NIXI 等注册局公开文档。
 */

return [
    // ============================================================
    // ICANN 通用 gTLD 标准生命周期（全部 gTLD 与新 gTLD 适用，权威）
    // ============================================================
    "_default" => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "type" => "gtld", "confidence" => "high"],

    // ---- 主流传统 gTLD ----
    "com"    => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Verisign", "confidence" => "high"],
    "net"    => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Verisign", "confidence" => "high"],
    "org"    => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "PIR", "confidence" => "high"],
    "info"   => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Identity Digital", "confidence" => "high"],
    "biz"    => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "GoDaddy Registry", "confidence" => "high"],
    "mobi"   => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Identity Digital", "confidence" => "high"],
    "pro"    => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Identity Digital", "confidence" => "high"],
    "name"   => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Verisign", "confidence" => "high"],
    "asia"   => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "DotAsia", "confidence" => "high"],
    "tel"    => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Telnames", "confidence" => "high"],
    "cat"    => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Fundació puntCAT", "confidence" => "high"],
    "jobs"   => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Employ Media", "confidence" => "high"],
    "travel" => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Identity Digital", "confidence" => "high"],

    // ---- 常见新 gTLD（均遵循 ICANN 标准 45/30/5）----
    "xyz"     => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "XYZ.com", "confidence" => "high"],
    "top"     => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Jiangsu Bangning", "confidence" => "high"],
    "site"    => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Radix", "confidence" => "high"],
    "online"  => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Radix", "confidence" => "high"],
    "store"   => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Radix", "confidence" => "high"],
    "tech"    => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Radix", "confidence" => "high"],
    "space"   => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Radix", "confidence" => "high"],
    "website" => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Radix", "confidence" => "high"],
    "fun"     => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Radix", "confidence" => "high"],
    "host"    => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Radix", "confidence" => "high"],
    "press"   => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Radix", "confidence" => "high"],
    "uno"     => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Radix", "confidence" => "high"],
    "shop"    => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "GMO Registry", "confidence" => "high"],
    "app"     => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Google Registry", "confidence" => "high"],
    "dev"     => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Google Registry", "confidence" => "high"],
    "page"    => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Google Registry", "confidence" => "high"],
    "new"     => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Google Registry", "confidence" => "high"],
    "vip"     => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Identity Digital", "confidence" => "high"],
    "club"    => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Identity Digital", "confidence" => "high"],
    "live"    => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Identity Digital", "confidence" => "high"],
    "life"    => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Identity Digital", "confidence" => "high"],
    "world"   => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Identity Digital", "confidence" => "high"],
    "today"   => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Identity Digital", "confidence" => "high"],
    "email"   => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Identity Digital", "confidence" => "high"],
    "group"   => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Identity Digital", "confidence" => "high"],
    "agency"  => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Identity Digital", "confidence" => "high"],
    "media"   => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Identity Digital", "confidence" => "high"],
    "news"    => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Identity Digital", "confidence" => "high"],
    "digital" => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Identity Digital", "confidence" => "high"],
    "network" => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Identity Digital", "confidence" => "high"],
    "solutions" => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Identity Digital", "confidence" => "high"],
    "services" => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Identity Digital", "confidence" => "high"],
    "ltd"     => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Identity Digital", "confidence" => "high"],
    "art"     => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "UNR / Identity Digital", "confidence" => "high"],
    "design"  => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Top Level Design", "confidence" => "high"],
    "studio"  => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Identity Digital", "confidence" => "high"],
    "work"    => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Radix", "confidence" => "high"],
    "blog"    => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Knock Knock Whois There", "confidence" => "high"],
    "wiki"    => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Top Level Design", "confidence" => "high"],
    "icu"     => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "ShortDot", "confidence" => "high"],
    "cyou"    => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "ShortDot", "confidence" => "high"],
    "sbs"     => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "ShortDot", "confidence" => "high"],
    "bond"    => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "ShortDot", "confidence" => "high"],
    "cloud"   => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Aruba", "confidence" => "high"],
    "pw"      => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Identity Digital", "confidence" => "high"],
    "wang"    => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Zodiac Wang", "confidence" => "high"],
    "ren"     => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "ZDNS", "confidence" => "high"],
    "xin"     => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Nova Registry", "confidence" => "high"],
    "beauty"  => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "XYZ.com", "confidence" => "high"],
    "monster" => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "XYZ.com", "confidence" => "high"],
    "quest"   => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "XYZ.com", "confidence" => "high"],
    "autos"   => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "XYZ.com", "confidence" => "high"],

    // ============================================================
    // Verisign 运营的 ccTLD（沿用 gTLD 标准周期）
    // ============================================================
    "tv"     => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Verisign (Tuvalu)", "confidence" => "high"],
    "cc"     => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "Verisign (Cocos)", "confidence" => "high"],

    // ============================================================
    // 主流 ccTLD（各注册局政策；confidence=est 为公开资料估算）
    // ============================================================

    // ---- 亚太 ----
    "cn"     => ["renewGrace" => 30, "redemption" => 30, "pendingDelete" => 5, "registry" => "CNNIC", "confidence" => "est"],
    "co"     => ["renewGrace" => 15, "redemption" => 30, "pendingDelete" => 5, "registry" => "GoDaddy Registry (.CO)", "confidence" => "high"],
    "io"     => ["renewGrace" => 30, "redemption" => 30, "pendingDelete" => 5, "registry" => "Identity Digital", "confidence" => "high"],
    "me"     => ["renewGrace" => 30, "redemption" => 30, "pendingDelete" => 5, "registry" => "doMEn", "confidence" => "high"],
    "in"     => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "NIXI", "confidence" => "high"],
    "jp"     => ["renewGrace" => 30, "redemption" => 0,  "pendingDelete" => 0, "registry" => "JPRS", "confidence" => "est"],        // 到期次月月底删除
    "kr"     => ["renewGrace" => 30, "redemption" => 0,  "pendingDelete" => 0, "registry" => "KISA", "confidence" => "est"],
    "tw"     => ["renewGrace" => 30, "redemption" => 30, "pendingDelete" => 0, "registry" => "TWNIC", "confidence" => "est"],
    "hk"     => ["renewGrace" => 30, "redemption" => 30, "pendingDelete" => 0, "registry" => "HKIRC", "confidence" => "est"],
    "sg"     => ["renewGrace" => 30, "redemption" => 30, "pendingDelete" => 5, "registry" => "SGNIC", "confidence" => "est"],
    "my"     => ["renewGrace" => 30, "redemption" => 30, "pendingDelete" => 0, "registry" => "MYNIC", "confidence" => "est"],
    "id"     => ["renewGrace" => 30, "redemption" => 40, "pendingDelete" => 0, "registry" => "PANDI", "confidence" => "est"],
    "ph"     => ["renewGrace" => 30, "redemption" => 30, "pendingDelete" => 0, "registry" => "dotPH", "confidence" => "est"],
    "th"     => ["renewGrace" => 30, "redemption" => 30, "pendingDelete" => 0, "registry" => "THNIC", "confidence" => "est"],
    "vn"     => ["registry" => "VNNIC", "predictable" => false, "confidence" => "est"],   // 政策特殊，无固定删除周期
    "au"     => ["renewGrace" => 30, "redemption" => 0,  "pendingDelete" => 0, "registry" => "auDA", "confidence" => "est"],
    "nz"     => ["renewGrace" => 90, "redemption" => 0,  "pendingDelete" => 0, "registry" => "InternetNZ", "confidence" => "est"],  // 约 90 天暂停后释放
    "pk"     => ["registry" => "PKNIC", "predictable" => false, "confidence" => "est"],
    "lk"     => ["renewGrace" => 30, "redemption" => 30, "pendingDelete" => 0, "registry" => "LK Domain Registry", "confidence" => "est"],
    "bd"     => ["registry" => "BTCL", "predictable" => false, "confidence" => "est"],
    "np"     => ["registry" => "Mercantile (.np)", "predictable" => false, "confidence" => "est"],

    // ---- 北美 ----
    "us"     => ["renewGrace" => 45, "redemption" => 30, "pendingDelete" => 5, "registry" => "GoDaddy Registry", "confidence" => "high"],
    "ca"     => ["renewGrace" => 40, "redemption" => 30, "pendingDelete" => 5, "registry" => "CIRA", "confidence" => "high"],
    "mx"     => ["renewGrace" => 30, "redemption" => 30, "pendingDelete" => 0, "registry" => "Registry .MX", "confidence" => "est"],

    // ---- 拉美 ----
    "br"     => ["renewGrace" => 30, "redemption" => 30, "pendingDelete" => 0, "registry" => "NIC.br", "confidence" => "est"],
    "ar"     => ["renewGrace" => 30, "redemption" => 0,  "pendingDelete" => 0, "registry" => "NIC Argentina", "confidence" => "est"],
    "cl"     => ["renewGrace" => 30, "redemption" => 0,  "pendingDelete" => 0, "registry" => "NIC Chile", "confidence" => "est"],
    "pe"     => ["renewGrace" => 30, "redemption" => 30, "pendingDelete" => 0, "registry" => "Red Científica Peruana", "confidence" => "est"],
    "ve"     => ["registry" => "CONATEL", "predictable" => false, "confidence" => "est"],
    "ec"     => ["registry" => "NIC.EC", "predictable" => false, "confidence" => "est"],
    "uy"     => ["registry" => "SeCIU", "predictable" => false, "confidence" => "est"],
    "cr"     => ["registry" => "NIC Costa Rica", "predictable" => false, "confidence" => "est"],

    // ---- 欧洲 ----
    "uk"     => ["renewGrace" => 90, "redemption" => 0,  "pendingDelete" => 2, "registry" => "Nominet", "confidence" => "high"],   // 约 90 天后取消并释放
    "eu"     => ["renewGrace" => 0,  "redemption" => 40, "pendingDelete" => 0, "registry" => "EURid", "confidence" => "high"],      // 到期后 40 天隔离期
    "nl"     => ["renewGrace" => 0,  "redemption" => 40, "pendingDelete" => 0, "registry" => "SIDN", "confidence" => "high"],       // 40 天隔离期
    "fr"     => ["renewGrace" => 30, "redemption" => 30, "pendingDelete" => 0, "registry" => "AFNIC", "confidence" => "high"],
    "re"     => ["renewGrace" => 30, "redemption" => 30, "pendingDelete" => 0, "registry" => "AFNIC", "confidence" => "high"],
    "yt"     => ["renewGrace" => 30, "redemption" => 30, "pendingDelete" => 0, "registry" => "AFNIC", "confidence" => "high"],
    "pm"     => ["renewGrace" => 30, "redemption" => 30, "pendingDelete" => 0, "registry" => "AFNIC", "confidence" => "high"],
    "tf"     => ["renewGrace" => 30, "redemption" => 30, "pendingDelete" => 0, "registry" => "AFNIC", "confidence" => "high"],
    "wf"     => ["renewGrace" => 30, "redemption" => 30, "pendingDelete" => 0, "registry" => "AFNIC", "confidence" => "high"],
    "be"     => ["renewGrace" => 40, "redemption" => 0,  "pendingDelete" => 0, "registry" => "DNS Belgium", "confidence" => "high"],  // 约 40 天隔离
    "it"     => ["renewGrace" => 15, "redemption" => 30, "pendingDelete" => 0, "registry" => "Registro.it (IIT-CNR)", "confidence" => "est"],
    "ch"     => ["renewGrace" => 40, "redemption" => 0,  "pendingDelete" => 0, "registry" => "SWITCH", "confidence" => "est"],       // 约 40 天后释放
    "li"     => ["renewGrace" => 40, "redemption" => 0,  "pendingDelete" => 0, "registry" => "SWITCH", "confidence" => "est"],
    "se"     => ["renewGrace" => 30, "redemption" => 30, "pendingDelete" => 0, "registry" => "IIS", "confidence" => "est"],          // 30 天宽限 + 30 天赎回
    "nu"     => ["renewGrace" => 30, "redemption" => 30, "pendingDelete" => 0, "registry" => "IIS", "confidence" => "est"],
    "dk"     => ["renewGrace" => 30, "redemption" => 30, "pendingDelete" => 0, "registry" => "Punktum dk", "confidence" => "est"],
    "pl"     => ["renewGrace" => 30, "redemption" => 0,  "pendingDelete" => 0, "registry" => "NASK", "confidence" => "est"],         // 到期后约 30 天释放
    "cz"     => ["renewGrace" => 60, "redemption" => 0,  "pendingDelete" => 0, "registry" => "CZ.NIC", "confidence" => "est"],       // 60 天保护后删除
    "pt"     => ["renewGrace" => 15, "redemption" => 15, "pendingDelete" => 0, "registry" => "DNS.PT", "confidence" => "est"],
    "ie"     => ["renewGrace" => 30, "redemption" => 30, "pendingDelete" => 0, "registry" => "IE Domain Registry", "confidence" => "est"],
    "ru"     => ["renewGrace" => 30, "redemption" => 0,  "pendingDelete" => 0, "registry" => "Coordination Center (.RU)", "confidence" => "est"],  // 30 天优先续费期
    "su"     => ["renewGrace" => 30, "redemption" => 0,  "pendingDelete" => 0, "registry" => "Coordination Center (.SU)", "confidence" => "est"],
    "ua"     => ["renewGrace" => 30, "redemption" => 0,  "pendingDelete" => 0, "registry" => "Hostmaster UA", "confidence" => "est"],
    "hu"     => ["renewGrace" => 30, "redemption" => 30, "pendingDelete" => 0, "registry" => "NIC.hu", "confidence" => "est"],
    "ro"     => ["registry" => "ICI / ROTLD", "predictable" => false, "confidence" => "est"],   // 一次性注册模式，无固定删除周期
    "hr"     => ["registry" => "CARNET", "predictable" => false, "confidence" => "est"],
    "sk"     => ["renewGrace" => 30, "redemption" => 30, "pendingDelete" => 0, "registry" => "SK-NIC", "confidence" => "est"],
    "si"     => ["renewGrace" => 30, "redemption" => 0,  "pendingDelete" => 0, "registry" => "Arnes / Register.si", "confidence" => "est"],
    "lt"     => ["renewGrace" => 30, "redemption" => 0,  "pendingDelete" => 0, "registry" => "DOMREG (Kaunas Univ.)", "confidence" => "est"],
    "lv"     => ["renewGrace" => 30, "redemption" => 0,  "pendingDelete" => 0, "registry" => "NIC.LV", "confidence" => "est"],
    "ee"     => ["renewGrace" => 30, "redemption" => 0,  "pendingDelete" => 0, "registry" => "Estonian Internet Foundation", "confidence" => "est"],
    "is"     => ["renewGrace" => 30, "redemption" => 0,  "pendingDelete" => 0, "registry" => "ISNIC", "confidence" => "est"],
    "lu"     => ["renewGrace" => 30, "redemption" => 0,  "pendingDelete" => 0, "registry" => "RESTENA / DNS-LU", "confidence" => "est"],
    "gr"     => ["registry" => "ICS-FORTH / GR-NIC", "predictable" => false, "confidence" => "est"],
    "rs"     => ["renewGrace" => 30, "redemption" => 30, "pendingDelete" => 0, "registry" => "RNIDS", "confidence" => "est"],
    "no"     => ["registry" => "Norid", "predictable" => false, "confidence" => "est"],           // 无标准 RGP，删除流程特殊
    "fi"     => ["renewGrace" => 30, "redemption" => 0,  "pendingDelete" => 0, "registry" => "Traficom", "confidence" => "est"],
    "at"     => ["registry" => "nic.at", "predictable" => false, "confidence" => "high"],          // 无固定赎回周期
    "de"     => ["registry" => "DENIC", "predictable" => false, "confidence" => "high"],            // 无标准赎回期，删除后进入 transit
    "es"     => ["registry" => "Red.es", "predictable" => false, "confidence" => "high"],

    // ---- 中东 / 非洲 ----
    "za"     => ["renewGrace" => 0,  "redemption" => 15, "pendingDelete" => 5, "registry" => "ZADNA (.co.za)", "confidence" => "est"],
    "ae"     => ["renewGrace" => 30, "redemption" => 30, "pendingDelete" => 0, "registry" => "aeDA (.ae)", "confidence" => "est"],
    "sa"     => ["registry" => "SaudiNIC", "predictable" => false, "confidence" => "est"],
    "il"     => ["registry" => "ISOC-IL", "predictable" => false, "confidence" => "est"],
    "tr"     => ["renewGrace" => 60, "redemption" => 0,  "pendingDelete" => 0, "registry" => "TRABIS / BTK", "confidence" => "est"],
    "ir"     => ["renewGrace" => 30, "redemption" => 0,  "pendingDelete" => 0, "registry" => "IRNIC", "confidence" => "est"],
    "ng"     => ["renewGrace" => 30, "redemption" => 30, "pendingDelete" => 0, "registry" => "NiRA", "confidence" => "est"],
    "ke"     => ["renewGrace" => 30, "redemption" => 30, "pendingDelete" => 0, "registry" => "KeNIC", "confidence" => "est"],
    "ma"     => ["registry" => "ANRT (.ma)", "predictable" => false, "confidence" => "est"],
    "eg"     => ["registry" => "EUN / .eg", "predictable" => false, "confidence" => "est"],

    // ============================================================
    // 无固定到期删除周期 / 一次性注册 / 政策特殊：仅展示阶段说明
    // ============================================================
    "tk"     => ["registry" => "Freenom", "predictable" => false, "confidence" => "high"],
    "ml"     => ["registry" => "Freenom", "predictable" => false, "confidence" => "high"],
    "ga"     => ["registry" => "Freenom", "predictable" => false, "confidence" => "high"],
    "cf"     => ["registry" => "Freenom", "predictable" => false, "confidence" => "high"],
    "gq"     => ["registry" => "Freenom", "predictable" => false, "confidence" => "high"],
];
