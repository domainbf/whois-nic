<?php
// 路由解析和调试
session_start();

// 调试日志（生产环境可删除）
error_log("=== NEW REQUEST ===");
error_log("URI: " . ($_SERVER['REQUEST_URI'] ?? ''));
error_log("QUERY: " . ($_SERVER['QUERY_STRING'] ?? ''));
error_log("SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? ''));

// 解析域名参数
$domain = null;

// 方法1：从GET参数获取
if (isset($_GET['domain']) && !empty(trim($_GET['domain']))) {
    $domain = trim($_GET['domain']);
    error_log("从GET参数获取域名: " . $domain);
}

// 方法2：从伪静态URL解析（如果GET参数为空）
if (!$domain && isset($_SERVER['REQUEST_URI'])) {
    if (preg_match('#^/([^/]+?)(?:\?|/|$)#', $_SERVER['REQUEST_URI'], $matches)) {
        $potentialDomain = $matches[1];
        if (preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)+$/', $potentialDomain)) {
            $domain = $potentialDomain;
            $_GET['domain'] = $domain;
            error_log("从URL路径解析域名: " . $domain);
        }
    }
}

error_log("最终使用的域名: " . ($domain ?? '无'));

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);
    header("Allow: GET");
    die("Method not allowed");
}

require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../vendor/autoload.php";

spl_autoload_register(function ($class) {
    if (str_starts_with($class, "Parser")) {
        require_once "Parsers/$class.php";
    } else {
        require_once "$class.php";
    }
});

use Pdp\SyntaxError;
use Pdp\UnableToResolveDomain;

function checkPassword()
{
    if (!SITE_PASSWORD) {
        return;
    }

    $password = $_COOKIE["password"] ?? null;
    if ($password === hash("sha256", SITE_PASSWORD)) {
        return;
    }

    $authorization = $_SERVER["HTTP_AUTHORIZATION"] ?? null;
    $bearerPrefix = "Bearer ";
    if ($authorization && str_starts_with($authorization, $bearerPrefix)) {
        $hash = substr($authorization, strlen($bearerPrefix));
        if ($hash === hash("sha256", SITE_PASSWORD)) {
            return;
        }
    }

    if (filter_var($_GET["json"] ?? 0, FILTER_VALIDATE_BOOL)) {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json");
        echo json_encode(["code" => 1, "msg" => "Incorrect password.", "data" => null]);
    } else {
        $requestUri = $_SERVER["REQUEST_URI"];
        if ($requestUri === BASE) {
            header("Location: " . BASE . "login");
        } else {
            header("Location: " . BASE . "login?redirect=" . urlencode($requestUri));
        }
    }
    die;
}

function cleanDomain($inputDomain = null)
{
    $domain = $inputDomain ?: ($_GET["domain"] ?? "");
    if (empty($domain)) {
        return "";
    }

    $domain = htmlspecialchars($domain, ENT_QUOTES, "UTF-8");
    $domain = trim(preg_replace(["/\s+/", "/\.{2,}/"], ["", "."], $domain), ".");

    $parsedUrl = parse_url($domain);
    if (!empty($parsedUrl["host"])) {
        $domain = $parsedUrl["host"];
    }

    if (DEFAULT_EXTENSION && strpos($domain, ".") === false) {
        $domain .= "." . DEFAULT_EXTENSION;
    }

    return $domain;
}

function getDataSource()
{
    $whois = filter_var($_GET["whois"] ?? 0, FILTER_VALIDATE_BOOL);
    $rdap = filter_var($_GET["rdap"] ?? 0, FILTER_VALIDATE_BOOL);

    if (!$whois && !$rdap) {
        $whois = $rdap = true;
    }

    $dataSource = [];
    if ($whois) {
        $dataSource[] = "whois";
    }
    if ($rdap) {
        $dataSource[] = "rdap";
    }

    return $dataSource;
}

checkPassword();

$domain = cleanDomain($domain);

$dataSource = [];
$fetchPrices = false;
$whoisData = null;
$rdapData = null;
$parser = new Parser("");
$error = null;

if ($domain) {
    $dataSource = getDataSource();
    $fetchPrices = filter_var($_GET["prices"] ?? 0, FILTER_VALIDATE_BOOL);

    try {
        $lookup = new Lookup($domain, $dataSource);
        $domain = $lookup->domain;
        $whoisData = $lookup->whoisData;
        $rdapData = $lookup->rdapData;
        $parser = $lookup->parser;

        if ($lookup->extension === "iana") {
            $fetchPrices = false;
        }
    } catch (Exception $e) {
        if ($e instanceof SyntaxError || $e instanceof UnableToResolveDomain) {
            $error = "'$domain' is not a valid domain";
        } else {
            $error = $e->getMessage();
        }
    }

    if (filter_var($_GET["json"] ?? 0, FILTER_VALIDATE_BOOL)) {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json");

        if ($error) {
            $value = ["code" => 1, "msg" => $error, "data" => null];
        } else {
            $value = ["code" => 0, "msg" => "Query successful", "data" => $parser];
        }

        $json = json_encode($value, JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            $value = ["code" => 1, "msg" => json_last_error_msg(), "data" => null];
            echo json_encode($value, JSON_UNESCAPED_UNICODE);
        } else {
            echo $json;
        }
        die;
    }
}

$manifestHref = "manifest";
if ($_SERVER["QUERY_STRING"] ?? "") {
    $manifestHref .= "?" . htmlspecialchars($_SERVER["QUERY_STRING"], ENT_QUOTES, "UTF-8");
}

$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$shareImage = BASE . "public/images/logo.png";

if ($domain) {
    if ($error) {
        $shareTitle = "$domain | 无效域名查询";
        $shareDescription = "查询的域名 '$domain' 是无效的。请尝试其他域名。";
    } elseif ($parser->unknown || $parser->reserved) {
        $shareTitle = "$domain | 未找到或保留";
        $shareDescription = "未找到域名 '$domain' 的信息，或该域名已被注册局保留。";
    } elseif ($parser->registered) {
        $shareTitle = "$domain | 已注册";
        $descriptionParts = [
            "域名 '$domain' 已被注册。",
            $parser->registrar ? "注册商: " . $parser->registrar : null,
            $parser->creationDate ? "注册日期: " . $parser->creationDate : null,
            $parser->expirationDate ? "到期日期: " . $parser->expirationDate : null
        ];
        $shareDescription = implode(" | ", array_filter($descriptionParts));
    } else {
        $shareTitle = "$domain | 可注册";
        $shareDescription = "域名 '$domain' 未被注册，可以尝试去注册。";
        $shareImage = BASE . "public/images/available_domain.png";
    }
} else {
    $shareTitle = SITE_TITLE;
    $shareDescription = SITE_DESCRIPTION;
}
?>

<!DOCTYPE html>
<html lang="en-US">
<head>
    <base href="<?= BASE; ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#e1f9f9">
    <meta name="description" content="<?= SITE_DESCRIPTION ?>">
    <meta name="keywords" content="<?= SITE_KEYWORDS ?>">
    <meta property="og:title" content="<?= htmlspecialchars($shareTitle, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:description" content="<?= htmlspecialchars($shareDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:image" content="<?= htmlspecialchars($shareImage, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:url" content="<?= htmlspecialchars($currentUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($shareTitle, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($shareDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($shareImage, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="shortcut icon" href="public/favicon.ico">
    <link rel="icon" href="public/images/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="public/images/apple-icon-180.png">
    <link rel="apple-touch-startup-image" href="public/images/apple-splash-2048-2732.jpg" media="(device-width: 1024px) and (device-height: 1366px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="public/images/apple-splash-1668-2388.jpg" media="(device-width: 834px) and (device-height: 1194px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="public/images/apple-splash-1536-2048.jpg" media="(device-width: 768px) and (device-height: 1024px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="public/images/apple-splash-1640-2360.jpg" media="(device-width: 820px) and (device-height: 1180px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="public/images/apple-splash-1668-2224.jpg" media="(device-width: 834px) and (device-height: 1112px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="public/images/apple-splash-1620-2160.jpg" media="(device-width: 810px) and (device-height: 1080px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="public/images/apple-splash-1488-2266.jpg" media="(device-width: 744px) and (device-height: 1133px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="public/images/apple-splash-1320-2868.jpg" media="(device-width: 440px) and (device-height: 956px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="public/images/apple-splash-1206-2622.jpg" media="(device-width: 402px) and (device-height: 874px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="public/images/apple-splash-1290-2796.jpg" media="(device-width: 430px) and (device-height: 932px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="public/images/apple-splash-1179-2556.jpg" media="(device-width: 393px) and (device-height: 852px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="public/images/apple-splash-1170-2532.jpg" media="(device-width: 390px) and (device-height: 844px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="public/images/apple-splash-1284-2778.jpg" media="(device-width: 428px) and (device-height: 926px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="public/images/apple-splash-1125-2436.jpg" media="(device-width: 375px) and (device-height: 812px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="public/images/apple-splash-1242-2688.jpg" media="(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="public/images/apple-splash-828-1792.jpg" media="(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="public/images/apple-splash-1242-2208.jpg" media="(device-width: 414px) and (device-height: 736px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="public/images/apple-splash-750-1334.jpg" media="(device-width: 375px) and (device-height: 667px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="public/images/apple-splash-640-1136.jpg" media="(device-width: 320px) and (device-height: 568px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)">
    <link rel="apple-touch-startup-image" href="public/images/apple-splash-2732-2048.jpg" media="(device-width: 1024px) and (device-height: 1366px) and (-webkit-device-pixel-ratio: 2) and (orientation: landscape)">
    <link rel="apple-touch-startup-image" href="public/images/apple-splash-2388-1668.jpg" media="(device-width: 834px) and (device-height: 1194px) and (-webkit-device-pixel-ratio: 2) and (orientation: landscape)">
    <link rel="apple-touch-startup-image" href="public/images/apple-splash-2048-1536.jpg" media="(device-width: 768px) and (device-height: 1024px) and (-webkit-device-pixel-ratio: 2) and (orientation: landscape)">
    <link rel="apple-touch-startup-image" href="public/images/apple-splash-2360-1640.jpg" media="(device-width: 820px) and (device-height: 1180px) and (-webkit-device-pixel-ratio: 2) and (orientation: landscape)">
    <link rel="apple-touch-startup-image" href="public/images/apple-splash-2224-1668.jpg" media="(device-width: 834px) and (device-height: 1112px) and (-webkit-device-pixel-ratio: 2) and (orientation: landscape)">
    <link rel="apple-touch-startup-image" href="public/images/apple-splash-2160-1620.jpg" media="(device-width: 810px) and (device-height: 1080px) and (-webkit-device-pixel-ratio: 2) and (orientation: landscape)">
    <link rel="apple-touch-startup-image" href="public/images/apple-splash-2266-1488.jpg" media="(device-width: 744px) and (device-height: 1133px) and (-webkit-device-pixel-ratio: 2) and (orientation: landscape)">
    <link rel="apple-touch-startup-image" href="public/images/apple-splash-2868-1320.jpg" media="(device-width: 440px) and (device-height: 956px) and (-webkit-device-pixel-ratio: 3) and (orientation: landscape)">
    <link rel="apple-touch-startup-image" href="public/images/apple-splash-2622-1206.jpg" media="(device-width: 402px) and (device-height: 874px) and (-webkit-device-pixel-ratio: 3) and (orientation: landscape)">
    <link rel="apple-touch-startup-image" href="public/images/apple-splash-2796-1290.jpg" media="(device-width: 430px) and (device-height: 932px) and (-webkit-device-pixel-ratio: 3) and (orientation: landscape)">
    <link rel="apple-touch-startup-image" href="public/images/apple-splash-2556-1179.jpg" media="(device-width: 393px) and (device-height: 852px) and (-webkit-device-pixel-ratio: 3) and (orientation: landscape)">
    <link rel="apple-touch-startup-image" href="public/images/apple-splash-2532-1170.jpg" media="(device-width: 390px) and (device-height: 844px) and (-webkit-device-pixel-ratio: 3) and (orientation: landscape)">
    <link rel="apple-touch-startup-image" href="public/images/apple-splash-2778-1284.jpg" media="(device-width: 428px) and (device-height: 926px) and (-webkit-device-pixel-ratio: 3) and (orientation: landscape)">
    <link rel="apple-touch-startup-image" href="public/images/apple-splash-2436-1125.jpg" media="(device-width: 375px) and (device-height: 812px) and (-webkit-device-pixel-ratio: 3) and (orientation: landscape)">
    <link rel="apple-touch-startup-image" href="public/images/apple-splash-2688-1242.jpg" media="(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 3) and (orientation: landscape)">
    <link rel="apple-touch-startup-image" href="public/images/apple-splash-1792-828.jpg" media="(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 2) and (orientation: landscape)">
    <link rel="apple-touch-startup-image" href="public/images/apple-splash-2208-1242.jpg" media="(device-width: 414px) and (device-height: 736px) and (-webkit-device-pixel-ratio: 3) and (orientation: landscape)">
    <link rel="apple-touch-startup-image" href="public/images/apple-splash-1334-750.jpg" media="(device-width: 375px) and (device-height: 667px) and (-webkit-device-pixel-ratio: 2) and (orientation: landscape)">
    <link rel="apple-touch-startup-image" href="public/images/apple-splash-1136-640.jpg" media="(device-width: 320px) and (device-height: 568px) and (-webkit-device-pixel-ratio: 2) and (orientation: landscape)">
    <link rel="manifest" href="<?= $manifestHref; ?>">
    <title><?= ($domain ? "$domain | " : "") . SITE_TITLE ?></title>
    <link rel="stylesheet" href="public/css/global.css">
    <link rel="stylesheet" href="public/css/index.css">
    <link rel="stylesheet" href="public/css/json.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:wght@300;400;500;600;700;900&display=swap">
    <?= CUSTOM_HEAD ?>
    <style>
        body {
            background-color: #ffffff;
            background-image: repeating-linear-gradient(0deg, transparent, transparent 19px, #eee 20px), repeating-linear-gradient(90deg, transparent, transparent 19px, #eee 20px);
            background-size: 20px 20px;
        }

        .search-and-button-container {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            justify-content: center;
        }

        .search-box {
            background: transparent !important;
            border: 2px solid #000000 !important;
            border-radius: 9999px !important;
            padding: 2px 4px !important;
            display: flex !important;
            align-items: center !important;
            height: 42px !important;
            flex: 1;
            max-width: 480px;
        }

        .search-box .input {
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
            color: #333 !important;
            font-size: 16px !important;
            padding: 0 12px !important;
            flex: 1 !important;
            outline: none !important;
            height: 36px !important;
            border-radius: 9999px !important;
            margin: 0 -2px !important;
        }

        .search-box .input::placeholder {
            color: #666 !important;
            opacity: 1 !important;
        }

        .search-box .input:focus {
            outline: none !important;
        }

        .search-box .search-clear {
            background: #f0f0f0 !important;
            border: 1px solid #ddd !important;
            border-radius: 50% !important;
            padding: 2px !important;
            margin: 0 4px !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            width: 32px !important;
            height: 32px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        .search-box .search-clear:hover {
            background: #e0e0e0 !important;
            border-color: #999 !important;
        }

        .search-box .search-clear svg {
            width: 14px !important;
            height: 14px !important;
        }

        .search-button {
            height: 42px !important;
            border-radius: 9999px !important;
            padding: 0 16px !important;
            white-space: nowrap !important;
            min-width: 80px !important;
        }

        .checkboxes {
            display: flex;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .message-data .message-title {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            grid-column: 1 / -1;
            margin-bottom: 1rem;
            font-size: 1rem;
            font-weight: 600;
            color: #222;
            text-align: left;
            flex-wrap: wrap;
            max-width: 100%;
            word-break: break-word;
        }

        .message-title a {
            flex-grow: 1;
            flex-shrink: 1;
            min-width: 0;
            word-break: break-all;
            overflow-wrap: break-word;
        }

        @media (max-width: 768px) {
            .search-and-button-container {
                flex-direction: row !important;
                align-items: center !important;
                gap: 8px !important;
                flex-wrap: wrap;
                margin-bottom: 6px;
            }

            .search-box {
                max-width: calc(100% - 88px);
                flex: 1;
                min-width: 200px;
            }

            .search-button {
                width: auto !important;
                min-width: 80px !important;
                flex-shrink: 0;
                font-size: 14px;
                padding: 0 12px;
            }

            .checkboxes {
                margin-top: 8px;
                gap: 8px;
                justify-content: center;
            }

            .message-data .message-title {
                font-size: 0.9rem;
                gap: 0.25rem;
                margin-bottom: 0.75rem;
            }

            .message-title a {
                font-size: 0.9rem;
                max-width: 85%;
            }

            .domain-info-box {
                max-width: 90%;
                margin-left: auto;
                margin-right: auto;
            }
        }

        @media (max-width: 480px) {
            .search-and-button-container {
                gap: 6px !important;
            }

            .search-box {
                max-width: calc(100% - 76px);
                min-width: 160px;
            }

            .search-button {
                min-width: 70px !important;
                font-size: 13px;
                padding: 0 10px;
            }

            .message-data .message-title {
                font-size: 0.85rem;
                gap: 0.2rem;
            }

            .message-title a {
                font-size: 0.85rem;
                max-width: 80%;
            }
        }

        .message-title .message-icon {
            width: 1.2em;
            height: 1.2em;
            flex-shrink: 0;
        }

        .message-data {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 1rem 1.5rem;
            margin-top: 1.5rem;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .checkbox-leading-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 18px;
            margin-right: 2px;
        }

        .message-label {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .message-icon-leading {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.2em;
            height: 1.2em;
        }

        .message.message-positive {
            background: transparent;
            border: none;
            box-shadow: none;
            padding: 0;
        }

        .message.message-positive .message-data {
            background: transparent;
            box-shadow: none;
            padding: 0;
        }

        header, main {
            background-color: transparent;
        }

        .raw-data-whois,
        .raw-data-rdap {
            background-color: #ffffff;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            position: relative;
            margin-bottom: 1rem;
            margin-top: 0;
        }

        .raw-data-rdap {
            display: none;
        }

        .raw-data-whois:only-child + .raw-data-rdap,
        .raw-data-rdap:only-child {
            display: block;
        }

        @media (max-width: 768px) {
            .raw-data-whois,
            .raw-data-rdap {
                padding: 1rem;
            }
        }

        @media (max-width: 480px) {
            .raw-data-whois,
            .raw-data-rdap {
                padding: 0.75rem;
            }
        }

        .raw-data-container pre {
            margin: 0 !important;
            padding: 0 !important;
            width: 100%;
            box-sizing: border-box;
        }

        .result-summary {
            display: flex;
            justify-content: center;
            margin-top: 1.5rem;
            margin-bottom: 2rem;
        }

        .result-box {
            background-color: #ffffff;
            padding: 1.5rem 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            font-size: 1.1rem;
            font-weight: 600;
            text-align: center;
            max-width: 800px;
        }

        .result-box p {
            margin: 0;
        }

        .domain-info-box.registered-status {
            display: none;
        }

        .domain-info-box:not(.registered-status) {
            display: block;
        }

        .domain-info-box {
            background-color: #fff;
            border: 2px solid #000;
            border-radius: 10px;
            padding: 8px 16px;
            margin-top: 10px;
            margin-bottom: 15px;
            font-weight: bold;
            font-size: 1.1em;
            max-width: fit-content;
            margin-left: auto;
            margin-right: auto;
        }

        .domain-info-box p {
            margin: 0;
            text-align: center;
        }

        .message-data .message-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
            font-size: 1.2rem;
            font-weight: 600;
            color: #222;
            flex-wrap: nowrap;
            max-width: 100%;
            word-break: normal;
            text-align: left;
        }

        .message-title a {
            flex-grow: 0;
            flex-shrink: 1;
            min-width: 0;
            word-break: break-all;
            overflow-wrap: break-word;
            font-size: 1.5em;
        }

        .domain-status-message {
            background-color: #28a745;
            color: #fff;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            white-space: nowrap;
            flex-shrink: 0;
        }

        @media (max-width: 768px) {
            .message-data .message-title {
                flex-wrap: wrap;
                gap: 0.5rem;
                font-size: 1rem;
            }

            .message-title a {
                font-size: 1.2em;
                flex-grow: 1;
            }

            .domain-status-message {
                margin-top: 8px;
            }
        }

        .message-tag-orange {
            background-color: #f28c38;
            color: #fff;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            white-space: nowrap;
        }

        .message-tag-purple {
            background-color: #6f42c1;
            color: #fff;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            white-space: nowrap;
        }

        .message-tag-cyan {
            background-color: #17a2b8;
            color: #fff;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            white-space: nowrap;
        }

        .message-tag-blue-dark {
            background-color: #004085;
            color: #fff;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <header>
        <div>
            <h1>
                <?php if ($domain): ?>
                    <a href="<?= BASE; ?>"><?= SITE_TITLE ?></a>
                <?php else: ?>
                    <?= SITE_TITLE ?>
                <?php endif; ?>
            </h1>
            <form action="<?= BASE; ?>" id="form" method="get">
                <div class="search-and-button-container">
                    <div class="search-box">
                        <input
                            autocapitalize="off"
                            autocomplete="domain"
                            autocorrect="off"
                            <?= $domain ? "" : "autofocus"; ?>
                            class="input search-input"
                            id="domain"
                            inputmode="url"
                            name="domain"
                            placeholder="示例：NIC.RW"
                            required
                            type="text"
                            value="<?= htmlspecialchars($domain ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        <button class="search-clear" id="domain-clear" type="button" aria-label="Clear">
                            <svg width="1em" height="1em" viewBox="0 0 16 16" fill="currentColor">
                                <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708" />
                            </svg>
                        </button>
                    </div>
                    <button class="button search-button">
                        <svg width="1em" height="1em" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" id="search-icon">
                            <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0" />
                        </svg>
                        <span>查询</span>
                    </button>
                </div>
                <div class="checkboxes">
                    <div class="checkbox">
                        <input <?= in_array("whois", $dataSource, true) ? "checked" : "" ?> class="checkbox-trigger" id="checkbox-whois" name="whois" type="checkbox" value="1">
                        <label class="checkbox-label" for="checkbox-whois">
                            <span class="checkbox-leading-icon">
                                <svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true">
                                    <circle cx="9" cy="9" r="9" fill="#222"/>
                                    <text x="9" y="13" text-anchor="middle" fill="#fff" font-size="12" font-family="Arial" font-weight="bold">W</text>
                                </svg>
                            </span>
                            WHOIS
                        </label>
                        <div class="checkbox-icon-wrapper">
                            <svg class="checkbox-icon checkbox-icon-checkmark" width="50" height="39.69" viewBox="0 0 50 39.69" aria-hidden="true">
                                <path d="M43.68 0L16.74 27.051 6.319 16.63l-6.32 6.32 16.742 16.74L50 6.32z" />
                            </svg>
                        </div>
                    </div>
                    <div class="checkbox">
                        <input <?= in_array("rdap", $dataSource, true) ? "checked" : "" ?> class="checkbox-trigger" id="checkbox-rdap" name="rdap" type="checkbox" value="1">
                        <label class="checkbox-label" for="checkbox-rdap">
                            <span class="checkbox-leading-icon">
                                <svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true">
                                    <circle cx="9" cy="9" r="9" fill="#222"/>
                                    <text x="9" y="13" text-anchor="middle" fill="#fff" font-size="12" font-family="Arial" font-weight="bold">R</text>
                                </svg>
                            </span>
                            RDAP
                        </label>
                        <div class="checkbox-icon-wrapper">
                            <svg class="checkbox-icon checkbox-icon-checkmark" width="50" height="39.69" viewBox="0 0 50 39.69" aria-hidden="true">
                                <path d="M43.68 0L16.74 27.051 6.319 16.63l-6.32 6.32 16.742 16.74L50 6.32z" />
                            </svg>
                        </div>
                    </div>
                    <div class="checkbox">
                        <input <?= $fetchPrices ? "checked" : "" ?> class="checkbox-trigger" id="checkbox-prices" name="prices" type="checkbox" value="1">
                        <label class="checkbox-label" for="checkbox-prices">
                            <span class="checkbox-leading-icon">
                                <svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true">
                                    <circle cx="9" cy="9" r="9" fill="#222"/>
                                    <text x="9" y="13" text-anchor="middle" fill="#fff" font-size="12" font-family="Arial" font-weight="bold">$</text>
                                </svg>
                            </span>
                            价格
                        </label>
                        <div class="checkbox-icon-wrapper">
                            <svg class="checkbox-icon checkbox-icon-checkmark" width="50" height="39.69" viewBox="0 0 50 39.69" aria-hidden="true">
                                <path d="M43.68 0L16.74 27.051 6.319 16.63l-6.32 6.32 16.742 16.74L50 6.32z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </form>
            <?php
                $resultMessage = null;
                if ($domain) {
                    if ($error) {
                        $resultMessage = "😂查询的这个域名是无效的哦。";
                    } elseif ($parser->unknown) {
                        $resultMessage = "🫣未找到该域名的信息。";
                    } elseif ($parser->reserved) {
                        $resultMessage = "🤬该死的注册局，把这个域名保留了。";
                    } elseif ($parser->registered) {
                        $resultMessage = "域名已注册。";
                    } else {
                        $resultMessage = "😁该域名未被注册，可以尝试去注册。";
                    }
                }
            ?>
            <?php if ($domain && $resultMessage): ?>
                <?php if ($parser->registered): ?>
                    <div class="domain-info-box registered-status">
                        <p><?= $resultMessage; ?></p>
                    </div>
                <?php else: ?>
                    <div class="domain-info-box">
                        <p><?= $resultMessage; ?></p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </header>
    <main>
        <?php if ($parser->registered): ?>
            <section class="messages">
                <div>
                    <div class="message message-positive">
                        <div class="message-data">
                            <h1 class="message-title">
                                <svg width="1em" height="1em" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" class="message-icon">
                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16" />
                                    <path d="m10.97 4.97-.02.022-3.473 4.425-2.093-2.094a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05" />
                                </svg>
                                <a href="http://<?= htmlspecialchars($domain, ENT_QUOTES, 'UTF-8'); ?>" rel="nofollow noopener noreferrer" target="_blank"><?= htmlspecialchars($domain, ENT_QUOTES, 'UTF-8'); ?></a>
                                <span class="domain-status-message">域名已注册</span>
                            </h1>
                            <?php if ($parser->registrar): ?>
                                <div class="message-label">
                                    <span class="message-icon-leading">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7m.5-3.5h-1a.5.5 0 0 1 0-1h1a.5.5 0 0 1 0 1m1-1a.5.5 0 0 1 .5-.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5z"/>
                                            <path d="M1.5 13.5a.5.5 0 0 1 .5-.5h2.5a.5.5 0 0 1 .5.5v2.5a.5.5 0 0 1-.5.5H2a.5.5 0 0 1-.5-.5z"/>
                                            <path d="M2 13h2.5a.5.5 0 0 1 .5.5v2.5a.5.5 0 0 1-.5.5H2a.5.5 0 0 1-.5-.5z"/>
                                            <path d="M2.5 1a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 .5.5v11.5a.5.5 0 0 1-.5.5H2.5a.5.5 0 0 1-.5-.5zM2.5 12.5a1 1 0 0 1 1-1h9a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-9a1 1 0 0 1-1-1z"/>
                                        </svg>
                                    </span>
                                    注册平台
                                </div>
                                <div>
                                    <?php if ($parser->registrarURL): ?>
                                        <a href="<?= htmlspecialchars($parser->registrarURL, ENT_QUOTES, 'UTF-8'); ?>" rel="nofollow noopener noreferrer" target="_blank"><?= htmlspecialchars($parser->registrar, ENT_QUOTES, 'UTF-8'); ?></a>
                                    <?php else: ?>
                                        <?= htmlspecialchars($parser->registrar, ENT_QUOTES, 'UTF-8'); ?>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($parser->creationDate): ?>
                                <div class="message-label">
                                    <span class="message-icon-leading">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M4.5 1a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1zm1 0h3a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5zm4.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5z"/>
                                            <path d="M12 4H4a1 1 0 0 0-1 1v9a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V5a1 1 0 0 0-1-1zm-8 1h8v9H4V5z"/>
                                            <path d="M8.5 8.5v2h-1v-2zm0-2h-1v2h1v-2zm0-2h-1v2h1v-2z"/>
                                        </svg>
                                    </span>
                                    创建日期
                                </div>
                                <div>
                                    <?php if ($parser->creationDateISO8601 === null): ?>
                                        <span><?= htmlspecialchars($parser->creationDate, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php elseif (str_ends_with($parser->creationDateISO8601, "Z")): ?>
                                        <span id="creation-date" data-iso8601="<?= htmlspecialchars($parser->creationDateISO8601, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?= htmlspecialchars($parser->creationDate, ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    <?php else: ?>
                                        <span id="creation-date" data-iso8601="<?= htmlspecialchars($parser->creationDateISO8601, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?= htmlspecialchars($parser->creationDate, ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($parser->expirationDate): ?>
                                <div class="message-label">
                                    <span class="message-icon-leading">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M4.5 1a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1zm1 0h3a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5zm4.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5z"/>
                                            <path d="M12 4H4a1 1 0 0 0-1 1v9a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V5a1 1 0 0 0-1-1zM4 5h8v9H4V5z"/>
                                            <path d="M8.5 8.5v2h-1v-2zm0-2h-1v2h1v-2zm0-2h-1v2h1v-2z"/>
                                        </svg>
                                    </span>
                                    到期日期
                                </div>
                                <div>
                                    <?php if ($parser->expirationDateISO8601 === null): ?>
                                        <span><?= htmlspecialchars($parser->expirationDate, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php elseif (str_ends_with($parser->expirationDateISO8601, "Z")): ?>
                                        <span id="expiration-date" data-iso8601="<?= htmlspecialchars($parser->expirationDateISO8601, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?= htmlspecialchars($parser->expirationDate, ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    <?php else: ?>
                                        <span id="expiration-date" data-iso8601="<?= htmlspecialchars($parser->expirationDateISO8601, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?= htmlspecialchars($parser->expirationDate, ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($parser->updatedDate): ?>
                                <div class="message-label">
                                    <span class="message-icon-leading">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M4 14a1 1 0 0 1-1-1V1a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1zm8-1v-1H4v1zm-8-2h8V1H4v10zm-1-3a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 0 1h-8a.5.5 0 0 1-.5-.5zm0-3a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 0 1h-8a.5.5 0 0 1-.5-.5z"/>
                                            <path d="M8 12a1 1 0 1 1 0-2 1 1 0 0 1 0 2zm0-3a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                                        </svg>
                                    </span>
                                    更新日期
                                </div>
                                <div>
                                    <?php if ($parser->updatedDateISO8601 === null): ?>
                                        <span><?= htmlspecialchars($parser->updatedDate, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php elseif (str_ends_with($parser->updatedDateISO8601, "Z")): ?>
                                        <span id="updated-date" data-iso8601="<?= htmlspecialchars($parser->updatedDateISO8601, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?= htmlspecialchars($parser->updatedDate, ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    <?php else: ?>
                                        <span id="updated-date" data-iso8601="<?= htmlspecialchars($parser->updatedDateISO8601, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?= htmlspecialchars($parser->updatedDate, ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($parser->availableDate): ?>
                                <div class="message-label">
                                    <span class="message-icon-leading">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M4 14a1 1 0 0 1-1-1V1a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1zm8-1v-1H4v1zm-8-2h8V1H4v10zm-1-3a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 0 1h-8a.5.5 0 0 1-.5-.5zm0-3a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 0 1h-8a.5.5 0 0 1-.5-.5z"/>
                                            <path d="M8 12a1 1 0 1 1 0-2 1 1 0 0 1 0 2zm0-3a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                                        </svg>
                                    </span>
                                    可用日期
                                </div>
                                <div>
                                    <?php if ($parser->availableDateISO8601 === null): ?>
                                        <span><?= htmlspecialchars($parser->availableDate, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php elseif (str_ends_with($parser->availableDateISO8601, "Z")): ?>
                                        <span id="available-date" data-iso8601="<?= htmlspecialchars($parser->availableDateISO8601, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?= htmlspecialchars($parser->availableDate, ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    <?php else: ?>
                                        <span id="available-date" data-iso8601="<?= htmlspecialchars($parser->availableDateISO8601, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?= htmlspecialchars($parser->availableDate, ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($parser->status): ?>
                                <div class="message-label">
                                    <span class="message-icon-leading">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16" />
                                            <path d="m10.97 4.97-.02.022-3.473 4.425-2.093-2.094a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05" />
                                        </svg>
                                    </span>
                                    域名状态
                                </div>
                                <div class="message-value-status">
                                    <?php foreach ($parser->status as $status): ?>
                                        <div>
                                            <?php if ($status["url"]): ?>
                                                <a href="<?= htmlspecialchars($status["url"], ENT_QUOTES, 'UTF-8'); ?>" rel="nofollow noopener noreferrer" target="_blank"><?= htmlspecialchars($status["text"], ENT_QUOTES, 'UTF-8'); ?></a>
                                            <?php else: ?>
                                                <?= htmlspecialchars($status["text"], ENT_QUOTES, 'UTF-8'); ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($parser->nameServers): ?>
                                <div class="message-label">
                                    <span class="message-icon-leading">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M5.5 10a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h5a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-5z"/>
                                            <path d="M12.44 1.44a.5.5 0 0 1 .12.55l-2.49 11.55a.5.5 0 0 1-.95.06L7 8.355l-2.043 4.65a.5.5 0 0 1-.95-.06L1.44 2a.5.5 0 0 1 .55-.12L8 4.288l5.44-2.968z"/>
                                        </svg>
                                    </span>
                                    NS服务器
                                </div>
                                <div class="message-value-name-servers">
                                    <?php foreach ($parser->nameServers as $nameServer): ?>
                                        <div>
                                            <?= htmlspecialchars($nameServer, ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if ($fetchPrices): ?>
                            <div class="message-price" id="message-price">
                                <div class="skeleton"></div>
                            </div>
                        <?php endif; ?>
                        <?php if ($parser->age || $parser->remaining || $parser->pendingDelete || $parser->gracePeriod || $parser->redemptionPeriod || $parser->status || $parser->dnssec || $parser->updatedDate): ?>
                            <div class="message-tags">
                                <?php if ($parser->age && $parser->ageSeconds): ?>
                                    <button class="message-tag message-tag-gray" id="age" data-seconds="<?= $parser->ageSeconds; ?>">
                                        <svg width="1em" height="1em" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                                            <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z" />
                                            <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0" />
                                        </svg>
                                        <span>已注册：<?= htmlspecialchars($parser->age, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </button>
                                    <?php if ($parser->ageSeconds < 30 * 24 * 60 * 60): ?>
                                        <span class="message-tag message-tag-green">新注册（30天内）</span>
                                    <?php elseif ($parser->ageSeconds > 5 * 365 * 24 * 60 * 60): ?>
                                        <span class="message-tag message-tag-blue">长期注册（5年以上）</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php if ($parser->remaining && $parser->remainingSeconds): ?>
                                    <button class="message-tag message-tag-gray" id="remaining" data-seconds="<?= $parser->remainingSeconds; ?>">
                                        <svg width="1em" height="1em" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                                            <path d="M2 1.5a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-1v1a4.5 4.5 0 0 1-2.557 4.06c-.29.139-.443.377-.443.59v.7c0 .213.154.451.443.59A4.5 4.5 0 0 1 12.5 13v1h1a.5.5 0 0 1 0 1h-11a.5.5 0 1 1 0-1h1v-1a4.5 4.5 0 0 1 2.557-4.06c.29.139.443.377.443-.59v-.7c0-.213-.154-.451-.443-.59A4.5 4.5 0 0 1 3.5 3V2h-1a.5.5 0 0 1-.5-.5m2.5.5v1a3.5 3.5 0 0 0 1.989 3.158c.533.256 1.011.791 1.011 1.491v.702c0 .7-.478 1.235-1.011 1.491A3.5 3.5 0 0 0 4.5 13v1h7v-1a3.5 3.5 0 0 0-1.989-3.158C8.978 9.586 8.5 9.052 8.5 8.351v-.702c0-.7.478-1.235 1.011-1.491A3.5 3.5 0 0 0 11.5 3V2z" />
                                        </svg>
                                        <span>距过期：<?= htmlspecialchars($parser->remaining, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </button>
                                    <?php if ($parser->remainingSeconds >= 0 && $parser->remainingSeconds < 30 * 24 * 60 * 60): ?>
                                        <span class="message-tag message-tag-yellow">即将过期（30天内）</span>
                                    <?php endif; ?>
                                    <?php if ($parser->remainingSeconds >= 0 && $parser->remainingSeconds < 7 * 24 * 60 * 60): ?>
                                        <span class="message-tag message-tag-orange">即将进入赎回期（7天内）</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php if ($parser->remainingSeconds < 0): ?>
                                    <span class="message-tag message-tag-red">已过期</span>
                                <?php endif; ?>
                                <?php if ($parser->pendingDelete): ?>
                                    <span class="message-tag message-tag-red">待删除</span>
                                <?php endif; ?>
                                <?php if ($parser->gracePeriod): ?>
                                    <span class="message-tag message-tag-yellow">宽限期</span>
                                <?php endif; ?>
                                <?php if ($parser->redemptionPeriod): ?>
                                    <span class="message-tag message-tag-blue">赎回期</span>
                                <?php endif; ?>
                                <?php if ($parser->status && (in_array("clientHold", array_column($parser->status, "text")) || in_array("serverHold", array_column($parser->status, "text")) || in_array("inactive", array_column($parser->status, "text")))): ?>
                                    <span class="message-tag message-tag-red">限制状态</span>
                                <?php endif; ?>
                                <?php if ($parser->status && (in_array("clientTransferProhibited", array_column($parser->status, "text")) || in_array("serverTransferProhibited", array_column($parser->status, "text")))): ?>
                                    <span class="message-tag message-tag-blue-dark">锁定状态</span>
                                <?php endif; ?>
                                <?php if ($parser->status && (in_array("pendingTransfer", array_column($parser->status, "text")) || in_array("pendingRenewal", array_column($parser->status, "text")))): ?>
                                    <span class="message-tag message-tag-orange">争议状态</span>
                                <?php endif; ?>
                                <?php if ($parser->status && !in_array("clientHold", array_column($parser->status, "text")) && !in_array("serverHold", array_column($parser->status, "text")) && !in_array("inactive", array_column($parser->status, "text")) && !$parser->pendingDelete && !$parser->gracePeriod && !$parser->redemptionPeriod): ?>
                                    <span class="message-tag message-tag-green">活跃状态</span>
                                <?php endif; ?>
                                <?php if (strlen($domain) < 5 || in_array($parser->extension, ['com', 'org', 'net'])): ?>
                                    <span class="message-tag message-tag-purple">高价值域名</span>
                                <?php endif; ?>
                                <?php if (in_array($parser->extension, ['ai', 'io', 'co'])): ?>
                                    <span class="message-tag message-tag-purple">高级顶级域</span>
                                <?php endif; ?>
                                <?php if ($parser->dnssec === "signedDelegation"): ?>
                                    <span class="message-tag message-tag-cyan">DNSSEC启用</span>
                                <?php endif; ?>
                                <?php if ($parser->updatedDateISO8601 && (strtotime($parser->updatedDateISO8601) > time() - 7 * 24 * 60 * 60)): ?>
                                    <span class="message-tag message-tag-blue">最近更新（7天内）</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>
        <?php if ($whoisData && $rdapData): ?>
            <section class="data-source">
                <div class="segmented">
                    <button class="segmented-item segmented-item-selected" id="data-source-whois" type="button">WHOIS</button>
                    <button class="segmented-item" id="data-source-rdap" type="button">RDAP</button>
                </div>
            </section>
        <?php endif; ?>
        <?php if ($whoisData || $rdapData): ?>
            <section class="raw-data">
                <?php if ($whoisData): ?>
                    <div class="raw-data-container">
                        <pre class="raw-data-whois" id="raw-data-whois" tabindex="0"><?= htmlspecialchars($whoisData, ENT_QUOTES, 'UTF-8'); ?></pre>
                    </div>
                <?php endif; ?>
                <?php if ($rdapData): ?>
                    <div class="raw-data-container">
                        <pre class="raw-data-rdap" id="raw-data-rdap" style="<?= $whoisData ? 'display: none;' : ''; ?>"><code class="language-json"><?= htmlspecialchars($rdapData, ENT_QUOTES, 'UTF-8'); ?></code></pre>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
    <?php require_once __DIR__ . "/footer.php"; ?>
    <button class="back-to-top" id="back-to-top">
        <svg width="1em" height="1em" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
            <path d="M8 12a.5.5 0 0 0 .5-.5V5.707l2.146 2.147a.5.5 0 0 0 .708-.708l-3-3a.5.5 0 0 0-.708 0l-3 3a.5.5 0 0 0 .708.708L7.5 5.707V11.5a.5.5 0 0 0 .5.5" fill-rule="evenodd" />
        </svg>
    </button>
    <script>
        window.addEventListener("DOMContentLoaded", function() {
            const domainElement = document.getElementById("domain");
            const domainClearElement = document.getElementById("domain-clear");

            if (domainElement && domainElement.value) {
                domainClearElement.classList.add("visible");
            }

            if (domainElement) {
                domainElement.addEventListener("input", (e) => {
                    if (e.target.value) {
                        domainClearElement.classList.add("visible");
                    } else {
                        domainClearElement.classList.remove("visible");
                    }
                });
            }

            if (domainClearElement) {
                domainClearElement.addEventListener("click", () => {
                    if (domainElement) {
                        domainElement.focus();
                        domainElement.select();
                        if (!document.execCommand("delete", false)) {
                            domainElement.setRangeText("");
                        }
                        domainClearElement.classList.remove("visible");
                    }
                });
            }

            const checkboxNames = ["whois", "rdap", "prices"];
            checkboxNames.forEach((name) => {
                const checkbox = document.getElementById(`checkbox-${name}`);
                if (checkbox) {
                    checkbox.addEventListener("change", () => {
                        document.getElementById("form").submit();
                    });
                }
            });

            const dataSourceWhois = document.getElementById("data-source-whois");
            const dataSourceRdap = document.getElementById("data-source-rdap");
            const rawDataWhois = document.getElementById("raw-data-whois");
            const rawDataRdap = document.getElementById("raw-data-rdap");

            if (dataSourceWhois && dataSourceRdap && rawDataWhois && rawDataRdap) {
                dataSourceWhois.addEventListener("click", () => {
                    dataSourceWhois.classList.add("segmented-item-selected");
                    dataSourceRdap.classList.remove("segmented-item-selected");
                    rawDataWhois.style.display = "block";
                    rawDataRdap.style.display = "none";
                });

                dataSourceRdap.addEventListener("click", () => {
                    dataSourceRdap.classList.add("segmented-item-selected");
                    dataSourceWhois.classList.remove("segmented-item-selected");
                    rawDataRdap.style.display = "block";
                    rawDataWhois.style.display = "none";
                });
            }

            const backToTop = document.getElementById("back-to-top");
            if (backToTop) {
                window.addEventListener("scroll", () => {
                    if (window.scrollY > 100) {
                        backToTop.classList.add("visible");
                    } else {
                        backToTop.classList.remove("visible");
                    }
                });

                backToTop.addEventListener("click", () => {
                    window.scrollTo({ top: 0, behavior: "smooth" });
                });
            }
        });
    </script>
</body>
</html>
