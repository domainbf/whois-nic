<?php

/**
 * 轻量多语言（i18n）支持
 * - 语言来源优先级：?lang= 查询参数（写入 cookie） > cookie `lang` > Accept-Language 探测 > 默认 zh
 * - 提供 t() 取译文（支持 sprintf 占位）、te() 直接输出转义文本
 * - 提供 i18n_lang() / i18n_html_lang() / i18n_js_payload() 供模板与前端使用
 * - 不改动任何查询/解析逻辑，仅作用于界面文案
 */

if (!defined("I18N_LOADED")) {
    define("I18N_LOADED", true);

    /** 支持的语言（顺序即菜单顺序） */
    function i18n_supported(): array
    {
        return [
            "zh"    => "简体中文",
            "zh-TW" => "繁體中文",
            "en"    => "English",
            "ja"    => "日本鬼子",
        ];
    }

    /** 根据浏览器 Accept-Language 探测默认语言 */
    function i18n_detect(): string
    {
        $header = strtolower($_SERVER["HTTP_ACCEPT_LANGUAGE"] ?? "");
        if ($header === "") return "zh";
        if (strpos($header, "zh-tw") !== false || strpos($header, "zh-hk") !== false || strpos($header, "zh-hant") !== false) return "zh-TW";
        if (strpos($header, "zh") !== false) return "zh";
        if (strpos($header, "ja") !== false) return "ja";
        if (strpos($header, "en") !== false) return "en";
        return "zh";
    }

    /** 初始化当前语言（在任何输出之前调用，以便写 cookie） */
    function i18n_init(): void
    {
        $supported = i18n_supported();
        $lang = null;

        if (isset($_GET["lang"]) && isset($supported[$_GET["lang"]])) {
            $lang = $_GET["lang"];
            // 仅在未发送响应头时写 cookie
            if (!headers_sent()) {
                setcookie("lang", $lang, [
                    "expires"  => time() + 31536000,
                    "path"     => "/",
                    "samesite" => "Lax",
                ]);
            }
            $_COOKIE["lang"] = $lang;
        } elseif (isset($_COOKIE["lang"]) && isset($supported[$_COOKIE["lang"]])) {
            $lang = $_COOKIE["lang"];
        } else {
            $lang = i18n_detect();
        }

        $GLOBALS["__LANG__"] = $lang;
    }

    /** 当前语言代码 */
    function i18n_lang(): string
    {
        return $GLOBALS["__LANG__"] ?? "zh";
    }

    /** html lang 属性值 */
    function i18n_html_lang(): string
    {
        $map = ["zh" => "zh-CN", "zh-TW" => "zh-Hant", "en" => "en", "ja" => "ja"];
        return $map[i18n_lang()] ?? "zh-CN";
    }

    /** 取译文（func_get_args 余下参数作 sprintf 实参） */
    function t(string $key): string
    {
        $dict = i18n_dict();
        $lang = i18n_lang();
        $val = $dict[$lang][$key] ?? ($dict["zh"][$key] ?? $key);
        $args = array_slice(func_get_args(), 1);
        if (!empty($args)) {
            return vsprintf($val, $args);
        }
        return $val;
    }

    /** 输出已转义译文 */
    function te(string $key): void
    {
        echo htmlspecialchars(call_user_func_array("t", func_get_args()), ENT_QUOTES, "UTF-8");
    }

    /** 供前端 JS 使用的精简译文负载（JSON 安全） */
    function i18n_js_payload(): string
    {
        $dict = i18n_dict();
        $lang = i18n_lang();
        $keys = [
            "loading_title", "loading_subtitle", "loading_step1", "loading_step2", "loading_step3",
            "rel_today", "rel_yesterday", "rel_years_ago", "rel_months_ago", "rel_days_ago",
            "dur_year", "dur_month", "dur_day", "dur_today",
            "age_title", "remaining_title", "date_click_hint",
            "price_register", "price_renew", "price_transfer", "price_failed", "price_nodata", "price_lowest",
            "history_today", "history_yesterday",
            "time_h", "time_m", "time_s",
        ];
        $out = [];
        foreach ($keys as $k) {
            $out[$k] = $dict[$lang][$k] ?? ($dict["zh"][$k] ?? $k);
        }
        $payload = [
            "lang"      => $lang,
            "dateStyle" => ($lang === "en") ? "iso" : "cjk", // en 用 YYYY-MM-DD，其余用 年月日
            "t"         => $out,
        ];
        return json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    /** 译文词典 */
    function i18n_dict(): array
    {
        static $dict = null;
        if ($dict !== null) return $dict;

        $dict = [
            "zh" => [
                // 顶栏 / 通用
                "brand_badge"      => "查询",
                "nav_home"         => "WHOIS 首页",
                "nav_theme"        => "切换深色 / 浅色模式",
                "nav_lang"         => "切换语言",
                // 搜索区
                "search_placeholder" => "输入域名进行查询",
                "search_button"    => "查询",
                "clear"            => "清除",
                "hotkey_query"     => "查询",
                "hotkey_clear"     => "清除 / 失焦",
                "history_title"    => "查询历史",
                "history_clear"    => "清除历史记录",
                "prev_page"        => "上一页",
                "next_page"        => "下一页",
                // 结果状态提示
                "title_invalid"    => "无效的域名后缀",
                "title_reserved"   => "该域名已被保留",
                "title_prohibited" => "该域名禁止注册",
                "title_unknown"    => "未找到注册信息",
                "title_available"  => "域名未注册。",
                "title_taken"      => "该域名已被注册",
                "title_error"      => "查询暂时失败",
                "msg_invalid"      => "请检查域名格式后重试。",
                "msg_reserved"     => "该域名已被注册局保留，暂不开放注册。",
                "msg_prohibited"   => "该域名被注册局禁止或限制注册，你可以联系注册局咨询具体情况。",
                "msg_unknown"      => "未找到该域名的注册信息。",
                "msg_available"    => "未查询到注册记录，您可以尝试注册；具体以注册局及注册商为准。",
                "msg_taken"        => "未获取到 WHOIS/RDAP 详情，但 DNS 记录显示该域名已被注册。",
                "msg_error"        => "注册局没有WHOIS/RDAP接口，很抱歉无法为你获取详细信息。",
                // 结果卡
                "status_expired"   => "已过期",
                "status_expiring"  => "即将到期",
                "status_active"    => "正常",
                "status_registered" => "已注册",
                "rem_expired"      => "已过期",
                "rem_days"         => "剩余 %d 天",
                "age_lt1"          => "<1 年",
                "age_years"        => "%d 年",
                "date_creation"    => "创建日期",
                "date_expiration"  => "到期日期",
                "date_updated"     => "更新日期",
                "date_available"   => "可用日期",
                "registrant_email" => "注册人邮箱",
                "registrant_phone" => "注册人电话",
                "card_status"      => "域名状态",
                "card_ns"          => "NS 服务器",
                "card_dnssec"      => "DNSSEC",
                "dnssec_signed"    => "已签名",
                "dnssec_unsigned"  => "未签名",
                "card_registrar"   => "注册商",
                "whois_server"     => "WHOIS 服务器",
                "registry_id"      => "注册局 ID",
                "registrar_iana"   => "注册商 ID",
                "registrar_address" => "注册商地址",
                "abuse_contact"    => "滥用联系",
                "registrant_info"  => "注册人信息",
                "email"            => "邮箱",
                "phone"            => "电话",
                "copy"             => "复制",
                "copied"           => "已复制",
                // 相对时间（服务端）
                "rel_today"        => "今天",
                "rel_years_ago"    => "%d年前",
                "rel_months_ago"   => "%d个月前",
                "rel_days_ago"     => "%d天前",
                "rel_yesterday"    => "昨天",
                // JS：时长 / 提示
                "dur_year"         => "%d年",
                "dur_month"        => "%d个月",
                "dur_day"          => "%d天",
                "dur_today"        => "今天",
                "age_title"        => "已经注册：%s",
                "remaining_title"  => "距离过期：%s",
                "date_click_hint"  => "，点击查看完整时间",
                "time_h"           => "时",
                "time_m"           => "分",
                "time_s"           => "秒",
                // JS：历史
                "history_today"    => "今天",
                "history_yesterday" => "昨天",
                // JS：价格
                "price_register"   => "注册",
                "price_renew"      => "续费",
                "price_transfer"   => "转移",
                "price_failed"     => "价格获取失败",
                "price_nodata"     => "暂无数据",
                "price_lowest"     => "最低价注册商",
                // JS：加载动画
                "loading_title"    => "正在查询 %s…",
                "loading_subtitle" => "RDAP · WHOIS · DNS",
                "loading_step1"    => "连接 RDAP 服务器…",
                "loading_step2"    => "查询 WHOIS 数据库…",
                "loading_step3"    => "解析注册信息…",
                // 页脚
                "footer_ann1"      => "集成 RDAP+WHOIS 双核驱动提供精准域名注册数据。",
                "footer_ann2"      => "本站提供域名查询服务，不储存任何搜索及查询数据信息。",
                "footer_ann3"      => "更多域名，可通过[DOMAIN.BF]进入列表查看所有域名。",
                "footer_ann4"      => "不记录·不储存·所有搜索查询数据仅保留在您本地浏览器。",
                "footer_badge"     => "每个域名都有一个故事",
                "footer_price_by"  => "域名价格数据由",
                "footer_provided"  => "友情提供",
                "footer_thanks"    => "鸣谢作者",
                "footer_opensource" => "开源项目",
                "footer_rights"    => "保留所有权利。",
            ],

            "zh-TW" => [
                "brand_badge"      => "查詢",
                "nav_home"         => "WHOIS 首頁",
                "nav_theme"        => "切換深色 / 淺色模式",
                "nav_lang"         => "切換語言",
                "search_placeholder" => "輸入網域進行查詢，例如 NIC.RW",
                "search_button"    => "查詢",
                "clear"            => "清除",
                "hotkey_query"     => "查詢",
                "hotkey_clear"     => "清除 / 失焦",
                "history_title"    => "查詢紀錄",
                "history_clear"    => "清除查詢紀錄",
                "prev_page"        => "上一頁",
                "next_page"        => "下一頁",
                "title_invalid"    => "無效的網域",
                "title_reserved"   => "此網域已被保留",
                "title_prohibited" => "此網域禁止註冊",
                "title_unknown"    => "未找到註冊資訊",
                "title_available"  => "此網域目前未註冊",
                "title_taken"      => "此網域已被註冊",
                "title_error"      => "查詢暫時失敗",
                "msg_invalid"      => "請檢查網域格式後重試。",
                "msg_reserved"     => "此網域已被註冊局保留，暫不開放註冊。",
                "msg_prohibited"   => "此網域被註冊局禁止或限制註冊。",
                "msg_unknown"      => "未找到此網域的註冊資訊。",
                "msg_available"    => "未查詢到註冊記錄，您可以嘗試註冊；能否成功以註冊局及註冊商的實際政策為準。",
                "msg_taken"        => "未取得 WHOIS/RDAP 詳情，但 DNS 記錄顯示此網域已被註冊。",
                "msg_error"        => "此後綴有效，但註冊局 WHOIS/RDAP 介面暫時無法存取，請稍後重試。",
                "status_expired"   => "已過期",
                "status_expiring"  => "即將到期",
                "status_active"    => "使用中",
                "status_registered" => "已註冊",
                "rem_expired"      => "已過期",
                "rem_days"         => "剩餘 %d 天",
                "age_lt1"          => "<1 年",
                "age_years"        => "%d 年",
                "date_creation"    => "建立日期",
                "date_expiration"  => "到期日期",
                "date_updated"     => "更新日期",
                "date_available"   => "可用日期",
                "registrant_email" => "註冊人信箱",
                "registrant_phone" => "註冊人電話",
                "card_status"      => "網域狀態",
                "card_ns"          => "NS 伺服器",
                "card_dnssec"      => "DNSSEC",
                "dnssec_signed"    => "已簽署",
                "dnssec_unsigned"  => "未簽署",
                "card_registrar"   => "註冊商",
                "whois_server"     => "WHOIS 伺服器",
                "registry_id"      => "註冊局 ID",
                "registrar_iana"   => "註冊商 IANA ID",
                "registrar_address" => "註冊商地址",
                "abuse_contact"    => "濫用聯絡",
                "registrant_info"  => "註冊人資訊",
                "email"            => "信箱",
                "phone"            => "電話",
                "copy"             => "複製",
                "copied"           => "已複製",
                "rel_today"        => "今天",
                "rel_years_ago"    => "%d年前",
                "rel_months_ago"   => "%d個月��",
                "rel_days_ago"     => "%d天前",
                "rel_yesterday"    => "昨天",
                "dur_year"         => "%d年",
                "dur_month"        => "%d個月",
                "dur_day"          => "%d天",
                "dur_today"        => "今天",
                "age_title"        => "已經註冊：%s",
                "remaining_title"  => "距離到期：%s",
                "date_click_hint"  => "，點擊查看完整時間",
                "time_h"           => "時",
                "time_m"           => "分",
                "time_s"           => "秒",
                "history_today"    => "今��",
                "history_yesterday" => "昨天",
                "price_register"   => "註冊",
                "price_renew"      => "續費",
                "price_transfer"   => "轉移",
                "price_failed"     => "價格取得失敗",
                "price_nodata"     => "暫無資料",
                "price_lowest"     => "最低價註冊商",
                "loading_title"    => "正在查詢 %s…",
                "loading_subtitle" => "RDAP · WHOIS · DNS",
                "loading_step1"    => "連接 RDAP 伺��器…",
                "loading_step2"    => "查詢 WHOIS 資料庫…",
                "loading_step3"    => "解析註冊資訊…",
                "footer_ann1"      => "整合 RDAP+WHOIS 雙核驅動，提供精準網域註冊資料。",
                "footer_ann2"      => "本站提供網域查詢服務，不儲存任何搜尋及查詢資料。",
                "footer_ann3"      => "出售中的網域，可👇點擊 [NIC.BN] 進入列表查看所有網域。",
                "footer_ann4"      => "不記錄·不儲存·所有搜尋查詢資料僅保留在您本機瀏覽器。",
                "footer_badge"     => "網域尋求合作",
                "footer_price_by"  => "網域價格資料由",
                "footer_provided"  => "友情提供",
                "footer_thanks"    => "鳴謝作者",
                "footer_opensource" => "開源專案",
                "footer_rights"    => "保留所有權利。",
            ],

            "en" => [
                "brand_badge"      => "Lookup",
                "nav_home"         => "WHOIS Home",
                "nav_theme"        => "Toggle dark / light mode",
                "nav_lang"         => "Change language",
                "search_placeholder" => "Enter a domain to look up, e.g. NIC.RW",
                "search_button"    => "Search",
                "clear"            => "Clear",
                "hotkey_query"     => "Search",
                "hotkey_clear"     => "Clear / Blur",
                "history_title"    => "Search history",
                "history_clear"    => "Clear history",
                "prev_page"        => "Previous",
                "next_page"        => "Next",
                "title_invalid"    => "Invalid domain",
                "title_reserved"   => "Domain is reserved",
                "title_prohibited" => "Registration prohibited",
                "title_unknown"    => "No registration found",
                "title_available"  => "This domain appears unregistered",
                "title_taken"      => "This domain is registered",
                "title_error"      => "Lookup temporarily failed",
                "msg_invalid"      => "Please check the format and try again.",
                "msg_reserved"     => "This domain is reserved by the registry and not open for registration.",
                "msg_prohibited"   => "Registration of this domain is prohibited or restricted by the registry.",
                "msg_unknown"      => "No registration information found for this domain.",
                "msg_available"    => "No registration record was found, so you may try to register it. Availability ultimately depends on the registry's and registrar's policies.",
                "msg_taken"        => "No WHOIS/RDAP details found, but DNS records show this domain is already registered.",
                "msg_error"        => "This TLD is valid, but the registry's WHOIS/RDAP service is temporarily unreachable. Please try again later.",
                "status_expired"   => "Expired",
                "status_expiring"  => "Expiring soon",
                "status_active"    => "Active",
                "status_registered" => "Registered",
                "rem_expired"      => "Expired",
                "rem_days"         => "%d days left",
                "age_lt1"          => "<1 yr",
                "age_years"        => "%d yr",
                "date_creation"    => "Created",
                "date_expiration"  => "Expires",
                "date_updated"     => "Updated",
                "date_available"   => "Available",
                "registrant_email" => "Registrant email",
                "registrant_phone" => "Registrant phone",
                "card_status"      => "Domain status",
                "card_ns"          => "Nameservers",
                "card_dnssec"      => "DNSSEC",
                "dnssec_signed"    => "Signed",
                "dnssec_unsigned"  => "Unsigned",
                "card_registrar"   => "Registrar",
                "whois_server"     => "WHOIS server",
                "registry_id"      => "Registry ID",
                "registrar_iana"   => "Registrar IANA ID",
                "registrar_address" => "Registrar address",
                "abuse_contact"    => "Abuse contact",
                "registrant_info"  => "Registrant info",
                "email"            => "Email",
                "phone"            => "Phone",
                "copy"             => "Copy",
                "copied"           => "Copied",
                "rel_today"        => "today",
                "rel_years_ago"    => "%d yr ago",
                "rel_months_ago"   => "%d mo ago",
                "rel_days_ago"     => "%d d ago",
                "rel_yesterday"    => "yesterday",
                "dur_year"         => "%dy",
                "dur_month"        => "%dmo",
                "dur_day"          => "%dd",
                "dur_today"        => "today",
                "age_title"        => "Registered for: %s",
                "remaining_title"  => "Until expiry: %s",
                "date_click_hint"  => " — click to see full time",
                "time_h"           => ":",
                "time_m"           => ":",
                "time_s"           => "",
                "history_today"    => "Today",
                "history_yesterday" => "Yesterday",
                "price_register"   => "Register",
                "price_renew"      => "Renew",
                "price_transfer"   => "Transfer",
                "price_failed"     => "Failed to load prices",
                "price_nodata"     => "No data",
                "price_lowest"     => "Lowest-price registrar",
                "loading_title"    => "Looking up %s…",
                "loading_subtitle" => "RDAP · WHOIS · DNS",
                "loading_step1"    => "Connecting to RDAP server…",
                "loading_step2"    => "Querying WHOIS database…",
                "loading_step3"    => "Parsing registration data…",
                "footer_ann1"      => "Powered by a dual RDAP + WHOIS engine for accurate domain registration data.",
                "footer_ann2"      => "This site provides domain lookups and stores no search or query data.",
                "footer_ann3"      => "Domains for sale — tap 👇 [NIC.BN] to browse the full list.",
                "footer_ann4"      => "No logging · no storage · all search data stays in your local browser.",
                "footer_badge"     => "Domain partnership",
                "footer_price_by"  => "Pricing data by",
                "footer_provided"  => "with thanks",
                "footer_thanks"    => "Thanks to",
                "footer_opensource" => "open-source project",
                "footer_rights"    => "All rights reserved.",
            ],

            "ja" => [
                "brand_badge"      => "検索",
                "nav_home"         => "WHOIS ホーム",
                "nav_theme"        => "ダーク / ライト切り替え",
                "nav_lang"         => "言語を切り替え",
                "search_placeholder" => "ドメインを入力して検索（例：NIC.RW）",
                "search_button"    => "検索",
                "clear"            => "クリア",
                "hotkey_query"     => "検索",
                "hotkey_clear"     => "クリア / フォーカス解除",
                "history_title"    => "検索履歴",
                "history_clear"    => "履歴を消去",
                "prev_page"        => "前へ",
                "next_page"        => "次へ",
                "title_invalid"    => "無効なドメイン",
                "title_reserved"   => "予約済みのドメイン",
                "title_prohibited" => "登録が禁���されています",
                "title_unknown"    => "登録情報が見つかりません",
                "title_available"  => "このドメインは未登録のようです",
                "title_taken"      => "このドメインは登録済みです",
                "title_error"      => "照会に一時的に失敗しました",
                "msg_invalid"      => "形式を確認して再度お試しください。",
                "msg_reserved"     => "このドメインはレジストリにより予約されており、登録できません。",
                "msg_prohibited"   => "このドメインはレジストリにより登録が禁止または制限されています。",
                "msg_unknown"      => "このドメインの登録情報が見つかりませんでした。",
                "msg_available"    => "登録記録は見つかりませんでした。登録を試すことができますが、可否はレジストリおよびレジストラの実際のポリシーによります。",
                "msg_taken"        => "WHOIS/RDAP の詳細は取得できませんでしたが、DNS 記録からこのドメインは登録済みです。",
                "msg_error"        => "この TLD は有効ですが、レジストリの WHOIS/RDAP に一時的に接続できません。しばらくして再度お試しください。",
                "status_expired"   => "期限切れ",
                "status_expiring"  => "まもなく期限切れ",
                "status_active"    => "有効",
                "status_registered" => "登録済み",
                "rem_expired"      => "期限切れ",
                "rem_days"         => "残り %d 日",
                "age_lt1"          => "1年未満",
                "age_years"        => "%d 年",
                "date_creation"    => "作成日",
                "date_expiration"  => "有効期限",
                "date_updated"     => "更新日",
                "date_available"   => "取得可能日",
                "registrant_email" => "登録者メール",
                "registrant_phone" => "登録者電話",
                "card_status"      => "ドメイン状態",
                "card_ns"          => "ネームサーバー",
                "card_dnssec"      => "DNSSEC",
                "dnssec_signed"    => "署名あり",
                "dnssec_unsigned"  => "署名なし",
                "card_registrar"   => "レジストラ",
                "whois_server"     => "WHOIS サーバー",
                "registry_id"      => "レジストリ ID",
                "registrar_iana"   => "レジストラ IANA ID",
                "registrar_address" => "レジストラ住所",
                "abuse_contact"    => "不正利用の連絡先",
                "registrant_info"  => "登録者情報",
                "email"            => "メール",
                "phone"            => "電話",
                "copy"             => "コピー",
                "copied"           => "コピー済み",
                "rel_today"        => "今日",
                "rel_years_ago"    => "%d年前",
                "rel_months_ago"   => "%dか月前",
                "rel_days_ago"     => "%d日前",
                "rel_yesterday"    => "昨日",
                "dur_year"         => "%d年",
                "dur_month"        => "%dか月",
                "dur_day"          => "%d日",
                "dur_today"        => "今日",
                "age_title"        => "登録期間：%s",
                "remaining_title"  => "期限まで：%s",
                "date_click_hint"  => "（クリックで詳細時刻）",
                "time_h"           => "時",
                "time_m"           => "分",
                "time_s"           => "秒",
                "history_today"    => "今日",
                "history_yesterday" => "昨日",
                "price_register"   => "登録",
                "price_renew"      => "更新",
                "price_transfer"   => "移管",
                "price_failed"     => "価格の取得に失敗",
                "price_nodata"     => "データなし",
                "price_lowest"     => "最安レジストラ",
                "loading_title"    => "%s を検索中…",
                "loading_subtitle" => "RDAP · WHOIS · DNS",
                "loading_step1"    => "RDAP サーバーに接続中…",
                "loading_step2"    => "WHOIS データベースを照会中…",
                "loading_step3"    => "登録情報を解析中…",
                "footer_ann1"      => "RDAP+WHOIS デュアルエンジンで正確なドメイン登録データを提供。",
                "footer_ann2"      => "当サイトはドメイン検索を提供し、検索・照会データを一切保存しません。",
                "footer_ann3"      => "販売中のドメインは👇[NIC.BN] をタップして一覧をご覧ください。",
                "footer_ann4"      => "記録なし・保存なし・すべての検索データはお使いのブラウザ内のみ。",
                "footer_badge"     => "ドメイン提携募集",
                "footer_price_by"  => "価格データ提供：",
                "footer_provided"  => "ご協力",
                "footer_thanks"    => "作者に感謝",
                "footer_opensource" => "オープンソース",
                "footer_rights"    => "All rights reserved.",
            ],
        ];

        return $dict;
    }
}
