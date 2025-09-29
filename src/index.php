<?php
session_start();

// 调试日志
error_log("=== NEW REQUEST ===");
error_log("URI: " . ($_SERVER['REQUEST_URI'] ?? ''));
error_log("QUERY: " . ($_SERVER['QUERY_STRING'] ?? ''));
error_log("SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? ''));

// 解析域名参数
$domain = null;

if (isset($_GET['domain']) && !empty(trim($_GET['domain']))) {
    $domain = trim($_GET['domain']);
    error_log("从GET参数获取域名: " . $domain);
}

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
$fetchBeiAn = false;
$whoisData = null;
$rdapData = null;
$parser = new Parser("");
$error = null;

if ($domain) {
  $dataSource = getDataSource();
  $fetchPrices = filter_var($_GET["prices"] ?? 0, FILTER_VALIDATE_BOOL);
  $fetchBeiAn = filter_var($_GET["beian"] ?? 0, FILTER_VALIDATE_BOOL);

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
        flex-wrap: nowrap;
    }

    .message-data .message-title {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1rem;
        font-size: 1.2rem;
        font-weight: 600;
        color: #222;
        flex-wrap: wrap;
        max-width: 100%;
        word-break: break-word;
    }

    .message-data .message-title a {
        flex-grow: 0;
        flex-shrink: 1;
        min-width: 0;
        word-break: break-all;
        overflow-wrap: break-word;
        font-size: 1.5em;
        max-width: 70%;
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
            flex-wrap: wrap;
            gap: 0.5rem;
            font-size: 1rem;
        }

        .message-data .message-title a {
            font-size: 1.2em;
            max-width: 65%;
        }

        .domain-info-box {
            max-width: 90%;
        }

        .raw-data-whois,
        .raw-data-rdap {
            padding: 1rem;
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

        .message-data .message-title a {
            font-size: 0.85rem;
            max-width: 60%;
        }

        .domain-info-box {
            max-width: 85%;
        }

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
        word-break: break-all;
        overflow-wrap: break-word;
    }

    .domain-info-box a {
        display: block;
        word-break: break-all;
        overflow-wrap: break-word;
        max-width: 100%;
    }

    .message-title .registered-status {
        background-color: #ffcccc;
        padding: 5px 10px;
        border-radius: 5px;
        margin-left: 10px;
    }

    @media (max-width: 768px) {
        .message-data .message-title {
            display: flex;
            align-items: flex-start;
            gap: 0.25rem;
            grid-column: 1 / -1;
            margin-bottom: 0.75rem;
            font-size: 0.9rem;
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
        .message-data .message-title {
            font-size: 0.85rem;
            gap: 0.2rem;
        }

        .message-title a {
            font-size: 0.85rem;
            max-width: 80%;
        }
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
        margin-top: 8px;
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
                  placeholder="试试查询示例：NIC.RW"
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
                  <text x="9" y="13" text-anchor="middle" fill="#fff" font-size="12" font-family="Arial" font-weight="bold">Ⓦ</text>
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
                  <text x="9" y="13" text-anchor="middle" fill="#fff" font-size="12" font-family="Arial" font-weight="bold">Ⓡ</text>
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
          <div class="checkbox">
            <input <?= $fetchBeiAn ? "checked" : "" ?> class="checkbox-trigger" id="checkbox-beian" name="beian" type="checkbox" value="1">
            <label class="checkbox-label" for="checkbox-beian">
              <span class="checkbox-leading-icon">
                <svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true">
                  <circle cx="9" cy="9" r="9" fill="#222"/>
                  <text x="9" y="13" text-anchor="middle" fill="#fff" font-size="12" font-family="Arial" font-weight="bold">㊭</text>
                </svg>
              </span>
              备案
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
                // 隐藏已注册状态的提示
                $resultMessage = null; 
            } else {
                $resultMessage = "😁该域名未被注册，可以尝试去注册。";
            }
        }
      ?>
      <?php if ($domain && $resultMessage): // 只有当 $domain 存在且 $resultMessage 不为空（即不是已注册状态）时才显示此框 ?>
        <div class="domain-info-box">
          <a href="http://<?= htmlspecialchars($domain, ENT_QUOTES, 'UTF-8'); ?>" rel="nofollow noopener noreferrer" target="_blank">
            <p style="margin-bottom: 5px; font-size: 1.2em;"><?= htmlspecialchars($domain, ENT_QUOTES, 'UTF-8'); ?></p>
          </a>
          <p><?= $resultMessage; ?></p>
        </div>
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
                  <span class="registered-status">域名已注册</span>
              </h1>
              <?php if ($parser->registrar): ?>
                <div class="message-label">
                  <span class="message-icon-leading">
                    <i class="fa-solid fa-credit-card"></i>
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
                    <i class="fa-solid fa-calendar-days"></i>
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
                    <i class="fa-solid fa-calendar-xmark"></i>
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
                    <i class="fa-solid fa-rotate"></i>
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
                    <i class="fa-solid fa-mobile-screen"></i>
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
                    <i class="fa-solid fa-circle-check"></i>
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
                    <i class="fa-solid fa-server"></i>
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
            <?php if ($fetchBeiAn): ?>
              <div class="message-beian" id="message-beian">
                <div class="skeleton"></div>
              </div>
            <?php endif; ?>
            <?php if ($parser->age || $parser->remaining || $parser->pendingDelete || $parser->gracePeriod || $parser->redemptionPeriod): ?>
              <div class="message-tags">
  <?php if ($parser->age): ?>
    <button class="message-tag message-tag-gray" id="age" data-seconds="<?= $parser->ageSeconds; ?>">
      <svg width="1em" height="1em" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
        <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z" />
        <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0" />
      </svg>
      <span>已经注册：<?= htmlspecialchars($parser->age, ENT_QUOTES, 'UTF-8'); ?></span>
    </button>
  <?php endif; ?>
  <?php if ($parser->remaining): ?>
    <button class="message-tag message-tag-gray" id="remaining" data-seconds="<?= $parser->remainingSeconds; ?>">
      <svg width="1em" height="1em" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
        <path d="M2 1.5a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-1v1a4.5 4.5 0 0 1-2.557 4.06c-.29.139-.443.377-.443.59v.7c0 .213.154.451.443.59A4.5 4.5 0 0 1 12.5 13v1h1a.5.5 0 0 1 0 1h-11a.5.5 0 1 1 0-1h1v-1a4.5 4.5 0 0 1 2.557-4.06c.29-.139.443-.377.443-.59v-.7c0-.213-.154-.451-.443-.59A4.5 4.5 0 0 1 3.5 3V2h-1a.5.5 0 0 1-.5-.5m2.5.5v1a3.5 3.5 0 0 0 1.989 3.158c.533.256 1.011.791 1.011 1.491v.702c0 .7-.478 1.235-1.011 1.491A3.5 3.5 0 0 0 4.5 13v1h7v-1a3.5 3.5 0 0 0-1.989-3.158C8.978 9.586 8.5 9.052 8.5 8.351v-.702c0-.7.478-1.235 1.011-1.491A3.5 3.5 0 0 0 11.5 3V2z" />
      </svg>
      <span>距离过期：<?= htmlspecialchars($parser->remaining, ENT_QUOTES, 'UTF-8'); ?></span>
    </button>
  <?php endif; ?>

  <?php if ($parser->ageSeconds && $parser->ageSeconds < 60 * 24 * 60 * 60): ?>
    <span class="message-tag message-tag-green">新注册</span>
  <?php endif; ?>
  <?php if (($parser->remainingSeconds ?? -1) >= 0 && $parser->remainingSeconds < 30 * 24 * 60 * 60): ?>
    <span class="message-tag message-tag-yellow">即将过期</span>
  <?php endif; ?>
  <?php if (($parser->ageSeconds ?? 0) >= 10 * 365 * 24 * 60 * 60): ?>
    <span class="message-tag message-tag-red">古董域名</span>
  <?php endif; ?>
  <?php if (($parser->remainingSeconds ?? 0) >= 5 * 365 * 24 * 60 * 60): ?>
    <span class="message-tag message-tag-blue">长期持有</span>
  <?php endif; ?>

  <?php 
  $domainParts = explode('.', $parser->domain ?? '');
  if ($parser && count($domainParts) > 0 && strlen($domainParts[0]) === 1): ?>
    <span class="message-tag message-tag-purple">单字符</span>
  <?php endif; ?>

  <?php if ($parser->pendingDelete): ?>
    <span class="message-tag message-tag-red">待删除</span>
  <?php elseif ($parser->remainingSeconds < 0): ?>
    <span class="message-tag message-tag-red">已过期</span>
  <?php endif; ?>
  <?php if ($parser->gracePeriod): ?>
    <span class="message-tag message-tag-yellow">宽限期</span>
  <?php elseif ($parser->redemptionPeriod): ?>
    <span class="message-tag message-tag-blue">赎回期</span>
  <?php endif; ?>

  <?php if (strpos($whoisData ?? '', 'transfer') !== false || strpos($whoisData ?? '', 'pendingTransfer') !== false): ?>
    <span class="message-tag message-tag-yellow">正在转移</span>
  <?php endif; ?>
  <?php 
  if ($parser->updatedDateISO8601 && strpos($whoisData ?? '', 'transfer') !== false): 
    $updatedDate = new DateTime($parser->updatedDateISO8601);
    $now = new DateTime();
    $diff = $now->diff($updatedDate)->days;
    if ($diff <= 90): ?>
      <span class="message-tag message-tag-blue">近期转移</span>
  <?php endif; endif; ?>

  <?php if (isset($parser->extension) && in_array(strtolower($parser->extension), ['com', 'net']) && strlen($domainParts[0]) < 6): ?>
  <span class="message-tag message-tag-green">高流量潜力</span>
<?php endif; ?>

  <?php if ($parser->remainingSeconds < 0 && !$parser->redemptionPeriod): ?>
    <span class="message-tag message-tag-red">过期待续</span>
  <?php endif; ?>

  <?php if (strpos($whoisData ?? '', 'clientHold') !== false || strpos($whoisData ?? '', 'serverHold') !== false): ?>
    <span class="message-tag message-tag-orange">已锁定</span>
  <?php endif; ?>

  <?php if (strpos($whoisData ?? '', 'deleted') !== false || strpos($whoisData ?? '', 'inactive') !== false): ?>
    <span class="message-tag message-tag-gray">已删除</span>
  <?php endif; ?>

  <?php if (strpos($whoisData ?? '', 'autoRenew') !== false || strpos($whoisData ?? '', 'renewPeriod') !== false): ?>
    <span class="message-tag message-tag-green">自动续费</span>
  <?php endif; ?>

  <?php if ((strpos($whoisData ?? '', 'clientHold') !== false || strpos($whoisData ?? '', 'serverHold') !== false) && !$parser->registered): ?>
    <span class="message-tag message-tag-yellow">暂停服务</span>
  <?php endif; ?>

  <?php if ((strpos($whoisData ?? '', 'fraud') !== false || $parser->pendingDelete) && ($parser->remainingSeconds ?? 0) < 7 * 24 * 60 * 60): ?>
    <span class="message-tag message-tag-red">高风险</span>
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
            <pre class="raw-data-rdap" id="raw-data-rdap"><code class="language-json"><?= htmlspecialchars($rdapData, ENT_QUOTES, 'UTF-8'); ?></code></pre>
          </div>
        <?php endif; ?>
      </section>
    <?php endif; ?>
  </main>
  <?php require_once __DIR__ . "/footer.php"; ?>
  <button class="back-to-top" id="back-to-top">
    <svg width="1em" height="1em" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
      <path d="M8 12a.5.5 0 0 0 .5-.5V5.707l2.146 2.147a.5.5 0 0 0 .708-.708l-3-3a.5.5 0 0 0-.708 0l-3 3a.5.5 0 1 0 .708.708L7.5 5.707V11.5a.5.5 0 0 0 .5.5" fill-rule="evenodd" />
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

      const checkboxNames = ["whois", "rdap", "prices", "beian"];
      <?php if ($domain): ?>
        checkboxNames.forEach((name) => {
          const checkbox = document.getElementById(`checkbox-${name}`);
          if (checkbox) {
            localStorage.setItem(`checkbox-${name}`, +checkbox.checked);
          }
        });
      <?php else: ?>
        const whoisValue = localStorage.getItem("checkbox-whois") || "0";
        const rdapValue = localStorage.getItem("checkbox-rdap") || "0";

        checkboxNames.forEach((name) => {
          const checkbox = document.getElementById(`checkbox-${name}`);
          if (checkbox) {
            if (!+whoisValue && !+rdapValue && name !== "prices" && name !== "beian") {
              checkbox.checked = true;
            } else {
              checkbox.checked = localStorage.getItem(`checkbox-${name}`) === "1";
            }
          }
        });
      <?php endif; ?>

      const form = document.getElementById("form");
      const searchIcon = document.getElementById("search-icon");
      if (form && searchIcon) {
        form.addEventListener("submit", () => {
          searchIcon.classList.add("searching");
        });
      }

      const backToTop = document.getElementById("back-to-top");
      if (backToTop) {
        backToTop.addEventListener("click", () => {
          window.scrollTo({
            behavior: "smooth",
            top: 0,
          });
        });

        window.addEventListener("scroll", () => {
          if (document.documentElement.scrollTop > 360) {
            if (!backToTop.classList.contains("visible")) {
              backToTop.classList.add("visible");
            }
          } else {
            if (backToTop.classList.contains("visible")) {
              backToTop.classList.remove("visible");
            }
          }
        });
      }
    });
  </script>
  <?php if ($whoisData || $rdapData): ?>
    <script>
      window.addEventListener("DOMContentLoaded", function() {
        function formatDuration(seconds) {
          const years = Math.floor(seconds / (365 * 24 * 60 * 60));
          seconds %= (365 * 24 * 60 * 60);
          const months = Math.floor(seconds / (30 * 24 * 60 * 60));
          seconds %= (30 * 24 * 60 * 60);
          const days = Math.floor(seconds / (24 * 60 * 60));
          seconds %= (24 * 60 * 60);
          const hours = Math.floor(seconds / (60 * 60));
          seconds %= (60 * 60);
          const minutes = Math.floor(seconds / 60);
          seconds %= 60;
          const formatted = [];
          if (years > 0) formatted.push(`${years}年`);
          if (months > 0) formatted.push(`${months}个月`);
          if (days > 0) formatted.push(`${days}天`);

          return formatted.join("");
        }

        function updateDateElementText(elementId) {
          const element = document.getElementById(elementId);
          if (element) {
            const iso8601 = element.dataset.iso8601;
            if (iso8601) {
              const date = new Date(iso8601);
              const year = date.getFullYear();
              const month = String(date.getMonth() + 1).padStart(2, "0");
              const day = String(date.getDate()).padStart(2, "0");

              element.innerText = `${year}年${month}月${day}日`;
            }
          }
        }

        updateDateElementText("creation-date");
        updateDateElementText("expiration-date");
        updateDateElementText("updated-date");
        updateDateElementText("available-date");

        const age = document.getElementById("age");
        if (age) {
            const ageSeconds = age.dataset.seconds;
            if (ageSeconds) {
                age.querySelector("span").innerText = `已经注册：${formatDuration(ageSeconds)}`;
            }
        }

        const remaining = document.getElementById("remaining");
        if (remaining) {
            const remainingSeconds = remaining.dataset.seconds;
            if (remainingSeconds) {
                remaining.querySelector("span").innerText = `距离过期：${formatDuration(remainingSeconds)}`;
            }
        }
      });
    </script>
    <script src="public/js/popper.min.js" defer></script>
    <script src="public/js/tippy-bundle.umd.min.js" defer></script>
    <script src="public/js/linkify.min.js" defer></script>
    <script src="public/js/linkify-html.min.js" defer></script>
    <script src="public/js/prism.js" defer></script>
    <script>
      window.addEventListener("DOMContentLoaded", function() {
        if (typeof tippy !== 'undefined') {
          tippy.setDefaultProps({
            arrow: false,
            offset: [0, 8],
            maxWidth: 200,
            allowHTML: false,
            theme: 'light-border',
            content: (reference) => reference.innerHTML,
          });
        }

        function updateDateElementTooltip(elementId) {
          const element = document.getElementById(elementId);
          if (element) {
            const iso8601 = element.dataset.iso8601;
            if (iso8601) {
              const date = new Date(iso8601);
              const year = date.getFullYear();
              const month = String(date.getMonth() + 1).padStart(2, "0");
              const day = String(date.getDate()).padStart(2, "0");
              const hours = String(date.getHours()).padStart(2, "0");
              const minutes = String(date.getMinutes()).padStart(2, "0");
              const seconds = String(date.getSeconds()).padStart(2, "0");
              const formattedDateTime = `${hours}时${minutes}分${seconds}秒`;


              if (typeof tippy !== 'undefined') {
                tippy(`#${elementId}`, {
                  content: formattedDateTime,
                  placement: "right",
                  appendTo: () => document.body,
                });
              }
            }
          }
        }

        updateDateElementTooltip("creation-date");
        updateDateElementTooltip("expiration-date");
        updateDateElementTooltip("updated-date");
        updateDateElementTooltip("available-date");

        function updateSecondsElementTooltip(elementId, prefix) {
          const element = document.getElementById(elementId);
          if (element) {
            const seconds = element.dataset.seconds;
            if (seconds) {
              let days = seconds / 24 / 60 / 60;
              days = seconds < 0 ? Math.ceil(days) : Math.floor(days);
              if (seconds < 0 && days === 0) {
                days = "-0";
              }
              if (typeof tippy !== 'undefined') {
                tippy(`#${elementId}`, {
                  content: `${prefix}: ${days} 天`,
                  placement: "right",
                });
              }
            }
          }
        }

        updateSecondsElementTooltip("age", "已经注册");
        updateSecondsElementTooltip("remaining", "距离过期");

        const dataSourceWHOIS = document.getElementById("data-source-whois");
        const dataSourceRDAP = document.getElementById("data-source-rdap");
        const rawDataWHOIS = document.getElementById("raw-data-whois");
        const rawDataRDAP = document.getElementById("raw-data-rdap");

        if (dataSourceWHOIS && dataSourceRDAP && rawDataWHOIS && rawDataRDAP) {
          dataSourceWHOIS.addEventListener("click", () => {
            if (dataSourceWHOIS.classList.contains("segmented-item-selected")) {
              return;
            }
            dataSourceWHOIS.classList.add("segmented-item-selected");
            dataSourceRDAP.classList.remove("segmented-item-selected");
            rawDataWHOIS.style.display = "block";
            rawDataRDAP.style.display = "none";
          });

          dataSourceRDAP.addEventListener("click", () => {
            if (dataSourceRDAP.classList.contains("segmented-item-selected")) {
              return;
            }
            dataSourceWHOIS.classList.remove("segmented-item-selected");
            dataSourceRDAP.classList.add("segmented-item-selected");
            rawDataWHOIS.style.display = "none";
            rawDataRDAP.style.display = "block";
          });
        }

        function linkifyRawData(element) {
          if (element && typeof linkifyHtml !== 'undefined') {
            element.innerHTML = linkifyHtml(element.innerHTML, {
              rel: "nofollow noopener noreferrer",
              target: "_blank",
              validate: {
                url: (value) => /^https?:\/\//.test(value),
              },
            });
          }
        }

        if (rawDataWHOIS) linkifyRawData(rawDataWHOIS);
        if (rawDataRDAP) linkifyRawData(rawDataRDAP);
      });
    </script>
  <?php endif; ?>
  <?php if ($fetchPrices): ?>
    <script>
      window.addEventListener("DOMContentLoaded", async () => {
        const messagePrice = document.getElementById("message-price");

        if (!messagePrice) {
          return;
        }

        const startTime = Date.now();

        try {
          const response = await fetch("https://api.tian.hu/whois.php?domain=<?= urlencode($domain); ?>&action=checkPrice");

          if (!response.ok) {
            throw new Error();
          }

          const data = await response.json();

          if (data.code !== "200") {
            throw new Error();
          }

          let innerHTML = "";

          const isPremium = data.data.premium === "true";

          if (isPremium) {
            innerHTML = `
              <button class="message-tag message-tag-purple" id="price-premium">
                <svg width="1em" height="1em" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                  <path d="M2.866 14.85c-.078.444.36.791.746.593l4.39-2.256 4.389 2.256c.386.198.824-.149.746-.592l-.83-4.73 3.522-3.356c.33-.314.16-.888-.282-.95l-4.898-.696L8.465.792a.513.513 0 0 0-.927 0L5.354 5.12l-4.898.696c-.441.062-.612.636-.283.95l3.523 3.356-.83 4.73zm4.905-2.767-3.686 1.894.694-3.957a.56.56 0 0 0-.163-.505L1.71 6.745l4.052-.576a.53.53 0 0 0 .393-.288L8 2.223l1.847 3.658a.53.53 0 0 0 .393.288l4.052.575-2.906 2.77a.56.56 0 0 0-.163.506l.694 3.957-3.686-1.894a.5.5 0 0 0-.461 0z" />
                </svg>
              </button>
            `;
          }

          let registerUSD = data.data.register_usd;
          let renewUSD = data.data.renew_usd;
          let registerCNY = data.data.register;
          let renewCNY = data.data.renew;

          registerUSD = registerUSD === "unknow" ? "?" : registerUSD;
          renewUSD = renewUSD === "unknow" ? "?" : renewUSD;
          registerCNY = registerCNY === "unknow" ? "?" : registerCNY;
          renewCNY = renewCNY === "unknow" ? "?" : renewCNY;

          innerHTML = `
            ${innerHTML}
            <button class="message-tag message-tag-gray" id="price-register">
              <span>注册: $${registerUSD}</span>
            </button>
            <button class="message-tag message-tag-gray" id="price-renew">
              <span>续费: $${renewUSD}</span>
            </button>
          `;

          setTimeout(() => {
            messagePrice.innerHTML = innerHTML;

            if (isPremium && typeof tippy !== 'undefined') {
              tippy("#price-premium", {
                content: "溢价",
                placement: "bottom",
              });
            }
            if (typeof tippy !== 'undefined') {
              tippy("#price-register", {
                content: `¥${registerCNY}`,
                placement: "bottom"
              });
              tippy("#price-renew", {
                content: `¥${renewCNY}`,
                placement: "bottom"
              });
            }
          }, Math.max(0, 500 - (Date.now() - startTime)));
        } catch {
          setTimeout(() => {
            messagePrice.innerHTML = `<span class="message-tag message-tag-pink">获取价格失败</span>`;
          }, Math.max(0, 500 - (Date.now() - startTime)));
        }
      });
    </script>
  <?php endif; ?>
  <?php if ($fetchBeiAn): ?>
    <script>
      window.addEventListener("DOMContentLoaded", async () => {
        const messageBeiAn = document.getElementById("message-beian");

        if (!messageBeiAn) {
          return;
        }

        messageBeiAn.style.transition = "opacity 0.3s ease";
        messageBeiAn.style.opacity = "0";

        const startTime = Date.now();

        try {
          const apiUrl = "https://beian.bug.kz/query/web?search=<?= urlencode($domain); ?>";
          const response = await fetch(apiUrl);

          if (!response.ok) {
            throw new Error("网络请求失败");
          }

          const data = await response.json();
          console.log("API响应数据:", data);

          if (data.code !== 200) {
            throw new Error(data.msg || "查询失败");
          }

          let innerHTML = "";
          const beianData = data.params && data.params.list && data.params.list.length > 0 ? data.params.list[0] : null;

          if (beianData) {
            const mainLicence = beianData.mainLicence || "无";
            const domainName = beianData.domain || "未知";
            const serviceLicence = beianData.serviceLicence || "无";
            const natureName = beianData.natureName || "未知";
            const unitName = beianData.unitName || "未知";
            const updateRecordTime = beianData.updateRecordTime ? new Date(beianData.updateRecordTime).toLocaleDateString() : "未知";
            const policeLicence = beianData.policeLicence || "无";

            innerHTML = `
              <div class="beian-info">
                <span class="beian-domain">${domainName}</span>
                <span class="beian-number">${mainLicence}</span>
                <span class="beian-tip">点击查看详情</span>
              </div>
            `;
          } else {
            innerHTML = `
              <div class="beian-info no-beian">
                <span class="beian-domain">${$domain}</span>
                <span class="no-beian-text">无备案信息</span>
              </div>
            `;
          }

          setTimeout(() => {
            messageBeiAn.innerHTML = innerHTML;
            messageBeiAn.style.opacity = "1";

            if (beianData && typeof tippy !== 'undefined') {
              tippy(".beian-info", {
                content: `
                  <div class="beian-tooltip">
                    <div class="tooltip-header">备案详细信息</div>
                    <div class="tooltip-content">
                      <span class="tooltip-item"><span class="tooltip-label">域名:</span><span class="tooltip-value">${beianData.domain || "未知"}</span></span>
                      <span class="tooltip-item"><span class="tooltip-label">备案号:</span><span class="tooltip-value">${beianData.mainLicence || "无"}</span></span>
                      <span class="tooltip-item"><span class="tooltip-label">服务许可证:</span><span class="tooltip-value">${beianData.serviceLicence || "无"}</span></span>
                      <span class="tooltip-item"><span class="tooltip-label">单位性质:</span><span class="tooltip-value">${beianData.natureName || "未知"}</span></span>
                      <span class="tooltip-item"><span class="tooltip-label">主办单位:</span><span class="tooltip-value">${beianData.unitName || "未知"}</span></span>
                      <span class="tooltip-item"><span class="tooltip-label">审核时间:</span><span class="tooltip-value">${beianData.updateRecordTime ? new Date(beianData.updateRecordTime).toLocaleDateString() : "未知"}</span></span>
                      <span class="tooltip-item"><span class="tooltip-label">公安局备案号:</span><span class="tooltip-value">&nbsp;${beianData.policeLicence || "无"}</span></span>
                    </div>
                  </div>
                `,
                placement: "bottom",
                allowHTML: true,
                theme: 'beian-tooltip',
                maxWidth: 1200
              });
            }
          }, Math.max(0, 500 - (Date.now() - startTime)));
        } catch (error) {
          console.error("备案查询错误:", error);
          setTimeout(() => {
            messageBeiAn.innerHTML = `
              <div class="beian-info error">
                <span class="beian-domain">${$domain}</span>
                <span class="error-text">获取失败: ${error.message}</span>
              </div>
            `;
            messageBeiAn.style.opacity = "1";
          }, Math.max(0, 500 - (Date.now() - startTime)));
        }
      });
    </script>

    <style>
      /* 备案信息样式 */
      .beian-info {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 5px 10px;
        border: none;
        background: transparent;
        font-family: 'Fraunces', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        transition: opacity 0.3s ease;
        justify-content: center;
        text-align: center;
      }

      .beian-domain {
        font-weight: 600;
        color: #000000;
        font-size: 14px;
        white-space: nowrap;
        text-decoration: none;
        margin-right: 10px;
      }

      .beian-number {
        font-weight: 600;
        color: #333333;
        font-size: 14px;
        white-space: nowrap;
        text-decoration: none;
        margin-right: 10px;
      }

      .beian-tip {
        font-size: 12px;
        color: #666666;
        white-space: nowrap;
        padding: 2px 5px;
        background: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 3px;
        position: absolute;
        right: 5px;
        bottom: -15px;
      }

      .beian-info.no-beian {
        border: none;
        justify-content: center;
      }

      .beian-info.no-beian .no-beian-text {
        color: #666666;
        font-weight: 500;
      }

      .beian-info.error {
        border: none;
        justify-content: center;
      }

      .beian-info.error .error-text {
        color: #ff3333;
        font-weight: 500;
      }

      /* 悬浮框样式 */
      .beian-tooltip {
        max-width: 1200px;
        padding: 0;
        background: #ffffff;
        border: 2px solid #000000;
        border-radius: 0;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        font-family: 'Fraunces', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        margin: 0;
      }

      .tooltip-header {
        background: #000000;
        color: #ffffff;
        padding: 8px 12px;
        font-weight: 600;
        font-size: 14px;
      }

      .tooltip-content {
        padding: 10px 12px;
        display: flex;
        gap: 10px;
        white-space: nowrap;
        background: #ffffff;
      }

      .tooltip-item {
        display: inline-flex;
        align-items: center;
        font-size: 13px;
        color: #333333;
      }

      .tooltip-label {
        font-weight: 600;
        color: #333333;
        margin-right: 8px;
        flex-shrink: 0;
        width: 80px;
        white-space: nowrap;
      }

      .tooltip-value {
        color: #666666;
        flex: 1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }

      .tippy-box[data-theme~='beian-tooltip'] {
        background: #ffffff;
        border: 2px solid #000000;
        border-radius: 0;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      }

      .tippy-box[data-theme~='beian-tooltip'] .tippy-arrow {
        color: #ffffff;
        border-color: #000000;
      }

      /* 响应式设计 */
      @media (max-width: 480px) {
        .beian-info {
          flex-direction: column;
          align-items: center;
          gap: 5px;
          padding: 5px;
        }

        .beian-tooltip {
          max-width: 90vw;
        }

        .tooltip-label {
          width: 70px;
        }

        .tooltip-content {
          flex-direction: column;
          white-space: normal;
        }

        .tooltip-value {
          white-space: normal;
          word-break: break-all;
        }

        .beian-tip {
          font-size: 10px;
          position: static;
          margin-top: 5px;
        }
      }
    </style>
<?php endif; ?>
<script src="https://kit.fontawesome.com/55e81b6986.js" crossorigin="anonymous"></script>
  <?= CUSTOM_SCRIPT ?>
</body>

</html>
