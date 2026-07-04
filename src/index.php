<?php
session_start();

// 解析域名参数
$domain = null;

if (isset($_GET['domain']) && !empty(trim($_GET['domain']))) {
    $domain = trim($_GET['domain']);
}

if (!$domain && isset($_SERVER['REQUEST_URI'])) {
    if (preg_match('#^/([^/]+?)(?:\?|/|$)#', $_SERVER['REQUEST_URI'], $matches)) {
        $potentialDomain = $matches[1];
        if (preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)+$/', $potentialDomain)) {
            $domain = $potentialDomain;
            $_GET['domain'] = $domain;
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
  http_response_code(405);
  header("Allow: GET");
  die("Method not allowed");
}

require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../vendor/autoload.php";

// 自动加载器与请求辅助函数（checkPassword / cleanDomain / getDataSource）
require_once __DIR__ . "/lib/helpers.php";

// 多语言（i18n）：在任何输出前初始化，以便正确写入 lang cookie
require_once __DIR__ . "/lib/i18n.php";
i18n_init();

use Pdp\SyntaxError;
use Pdp\UnableToResolveDomain;

// ---- 搜索联想：批量 DNS 状态查询接口（?api=domain-status&domains=a.com,b.net）----
// 供 autocomplete.js 判断每个候选域名是否"已注册 / 已建站"，仅做轻量 DNS 探测。
if (isset($_GET["api"]) && $_GET["api"] === "domain-status") {
  header("Content-Type: application/json; charset=utf-8");
  header("Cache-Control: public, max-age=60");

  $raw = isset($_GET["domains"]) ? (string) $_GET["domains"] : "";
  $list = array_filter(array_map("trim", explode(",", $raw)));
  $list = array_slice(array_unique($list), 0, 12); // 限制单次数量，避免滥用

  $out = [];
  foreach ($list as $d) {
    $clean = strtolower($d);
    // 基本格式校验，避免对无效输入做 DNS 查询
    if (!preg_match('/^[a-z0-9\x{4e00}-\x{9fa5}]([a-z0-9\x{4e00}-\x{9fa5}-]{0,61}[a-z0-9\x{4e00}-\x{9fa5}])?(\.[a-z0-9\x{4e00}-\x{9fa5}]([a-z0-9\x{4e00}-\x{9fa5}-]{0,61}[a-z0-9\x{4e00}-\x{9fa5}])?)+$/u', $clean)) {
      continue;
    }
    $out[$d] = domainDnsStatusCached($clean);
  }

  echo json_encode($out, JSON_UNESCAPED_UNICODE);
  exit;
}

// ---- 网站图标代理接口（?api=favicon&domain=example.com）----
// 为什么需要服务端代理：Google / DuckDuckGo 的 favicon 服务在国内浏览器常被墙，
// 直连站点 /favicon.ico 又受 HTTPS 证书 / 跨域 / 无图标等问题困扰，导致图标加载失败。
// 本接口在 Vercel（美国）服务端抓取图标再以"同源图片"返回，国内一定可达、无 CORS，
// 浏览器还能长期缓存。前端只需 <img src="?api=favicon&domain=x">。
if (isset($_GET["api"]) && $_GET["api"] === "favicon") {
  $d = isset($_GET["domain"]) ? strtolower(trim($_GET["domain"])) : "";

  // 严格校验域名，避免 SSRF（只允许合法域名字符）
  if (!preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?(\.[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)+$/', $d)) {
    http_response_code(400);
    exit;
  }

  // 多源逐个尝试：Google（清晰、覆盖广，sz=128 更锐利）→ DuckDuckGo → 站点自身 /favicon.ico
  $sources = [
    "https://www.google.com/s2/favicons?sz=128&domain=" . urlencode($d),
    "https://icons.duckduckgo.com/ip3/" . urlencode($d) . ".ico",
    "https://" . $d . "/favicon.ico",
  ];

  // 校验二进制是否为真图片：按魔数识别 PNG/ICO/GIF/JPEG/WebP/SVG，
  // 避免把 HTML 错误页当作图标缓存下来。
  $looksLikeImage = function ($bin) {
    if (strlen($bin) < 24) {
      return false;
    }
    $h = substr($bin, 0, 12);
    if (substr($h, 0, 8) === "\x89PNG\r\n\x1a\n") return "image/png";           // PNG
    if (substr($h, 0, 4) === "\x00\x00\x01\x00") return "image/x-icon";          // ICO
    if (substr($h, 0, 3) === "GIF") return "image/gif";                          // GIF
    if (substr($h, 0, 2) === "\xff\xd8") return "image/jpeg";                    // JPEG
    if (substr($h, 0, 4) === "RIFF" && substr($bin, 8, 4) === "WEBP") return "image/webp"; // WebP
    if (stripos($bin, "<svg") !== false) return "image/svg+xml";                 // SVG
    return false;
  };

  $img = null;
  $ctype = "image/png";
  foreach ($sources as $src) {
    $ch = curl_init($src);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_MAXREDIRS => 3,
      CURLOPT_CONNECTTIMEOUT => 3,
      CURLOPT_TIMEOUT => 5,
      CURLOPT_SSL_VERIFYPEER => false, // 站点证书不规范时也尽量取到图标
      CURLOPT_USERAGENT => "Mozilla/5.0 (compatible; WhoisFaviconBot/1.0)",
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // 有效图标：HTTP 2xx、内容够大、且魔数确认是真图片
    if ($body !== false && $code >= 200 && $code < 300 && strlen($body) > 70) {
      $detected = $looksLikeImage($body);
      if ($detected) {
        $img = $body;
        $ctype = $detected;
        break;
      }
    }
  }

  if ($img === null) {
    // 全部失败：返回 204，前端据此保留通用链接图标
    header("Cache-Control: public, max-age=86400");
    http_response_code(204);
    exit;
  }

  header("Content-Type: " . $ctype);
  header("Cache-Control: public, max-age=604800"); // 缓存 7 天，避免重复抓取
  header("Content-Length: " . strlen($img));
  echo $img;
  exit;
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
$invalidDomain = false; // 仅当域名格式/后缀真正非法时为 true
$dnsActive = false; // DNS 层面是否检测到域名已被注册/在用

if ($domain) {
  $dataSource = getDataSource();
  $fetchPrices = filter_var($_GET["prices"] ?? 0, FILTER_VALIDATE_BOOL);
  $fetchBeiAn = filter_var($_GET["beian"] ?? 0, FILTER_VALIDATE_BOOL);

  try {
    $queryStart = microtime(true);
    $lookup = new Lookup($domain, $dataSource);
    // 归一化后回填；若解析器未返回域名，则保留用户查询值，确保搜索框始终回显
    $domain = $lookup->domain ?: $domain;
    $whoisData = $lookup->whoisData;
    $rdapData = $lookup->rdapData;
    $parser = $lookup->parser;

    if ($lookup->extension === "iana") {
      $fetchPrices = false;
    }

    // 当 WHOIS/RDAP 判定为"未注册/未知"（既非已注册，也非保留/禁止）时，
    // 追加 DNS 校验以提升准确性：若存在 NS/A/AAAA/MX 记录，说明域名其实已被注册。
    if (
      $lookup->extension !== "iana" &&
      !$parser->registered &&
      !$parser->reserved &&
      !$parser->prohibited
    ) {
      $dnsActive = domainHasDnsRecords($domain);
    }

    // 计时覆盖 WHOIS/RDAP + DNS 兜底的完整服务端查询耗时，
    // 使显示的耗时与真实等待一致（此前仅计 Lookup，未含 DNS 兜底）。
    $queryElapsed = microtime(true) - $queryStart;
  } catch (Exception $e) {
    if ($e instanceof SyntaxError || $e instanceof UnableToResolveDomain) {
      // 真正的格式/后缀非法：才算"无效域名"
      $invalidDomain = true;
      $error = "'$domain' is not a valid domain";
    } else {
      // WHOIS/RDAP 查询失败（服务器不可达、超时、注册局接口异常等）：
      // 这不代表域名无效。退一步用 DNS 兜底判断是否已注册，尽量给出有用结果。
      $error = $e->getMessage();
      $dnsActive = domainHasDnsRecords($domain);
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

// 分享/Manifest 元数据��$manifestHref, $currentUrl, $shareImage, $shareTitle, $shareDescription）
require __DIR__ . "/lib/share-meta.php";
?>
  <!DOCTYPE html>
  <html lang="<?= htmlspecialchars(i18n_html_lang(), ENT_QUOTES, 'UTF-8'); ?>">

  <?php require __DIR__ . "/partials/head.php"; ?>

<body data-has-domain="<?= $domain ? "1" : "0"; ?>">
  <?php require __DIR__ . "/partials/topbar.php"; ?>
  <?php require __DIR__ . "/partials/search-form.php"; ?>
  <main>
    <?php require __DIR__ . "/partials/result.php"; ?>
    <?php require __DIR__ . "/partials/raw-data.php"; ?>
  </main>
  <?php require_once __DIR__ . "/footer.php"; ?>
  <button class="back-to-top" id="back-to-top">
    <svg width="1em" height="1em" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
      <path d="M8 12a.5.5 0 0 0 .5-.5V5.707l2.146 2.147a.5.5 0 0 0 .708-.708l-3-3a.5.5 0 0 0-.708 0l-3 3a.5.5 0 1 0 .708.708L7.5 5.707V11.5a.5.5 0 0 0 .5.5" fill-rule="evenodd" />
    </svg>
  </button>
  <?php require __DIR__ . "/partials/scripts.php"; ?>
  <?= CUSTOM_SCRIPT ?>
</body>
<script defer src="https://umami-rho-blue.vercel.app/script.js" data-website-id="ad534fcf-b898-4f4c-b80b-c910820e206f"></script>
</html>
