<?php

// 自动加载器与请求辅助函数（从 index.php 抽离，行为保持不变）

// 静态资源版本化：给 CSS/JS 追加 ?v=<版本>，实现每次部署后缓存击穿。
// 这些资源带 30 天强缓存（max-age=2592000），无版本号会导致浏览器长期使用旧文件。
// 注意：Vercel 部署时会把所有文件的 mtime 归一到同一固定值（如 1540000000），
// 因此 filemtime 在生产环境永远不变、无法击穿缓存。改为优先使用每次部署都变化的
// 部署标识（git commit SHA / 部署 ID），本地开发再回退到 filemtime。
function asset_version(): string
{
  static $ver = null;
  if ($ver !== null) {
    return $ver;
  }
  // Vercel 系统环境变量：每次部署都不同
  $candidates = ["VERCEL_GIT_COMMIT_SHA", "VERCEL_DEPLOYMENT_ID", "VERCEL_URL"];
  foreach ($candidates as $key) {
    $val = getenv($key);
    if (is_string($val) && $val !== "") {
      return $ver = substr(preg_replace('/[^A-Za-z0-9]/', "", $val), 0, 12);
    }
  }
  // 本地开发回退：用 index.php 的 mtime（本地不会被归一化）
  $mt = @filemtime(__DIR__ . "/../index.php");
  return $ver = $mt ? (string) $mt : (string) time();
}

function asset(string $relPath): string
{
  return $relPath . "?v=" . asset_version();
}

spl_autoload_register(function ($class) {
  // 类文件位于 src/ 目录（本文件在 src/lib/，故上溯一级）
  $base = __DIR__ . "/..";
  if (str_starts_with($class, "Parser")) {
    require_once "$base/Parsers/$class.php";
  } else {
    require_once "$base/$class.php";
  }
});

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

/**
 * 通过 DNS 记录判断域名是否实际"在用/已被注册"。
 *
 * 当 WHOIS / RDAP 查不到信息时（很多 ccTLD 无公开 WHOIS/RDAP），
 * 仅凭"查不到"就判定为"可注册"并不准确。若域名存在权威 NS 记录
 * （被委派），或存在 A/AAAA/MX 记录（正在解析/收发邮件），则该域名
 * 几乎可以确定已被注册。此函数据此提供更可靠的补充判断。
 *
 * @return bool true 表示 DNS 层面检测到该域名已被注册/在用
 */
function domainHasDnsRecords($domain)
{
  if (!$domain || strpos($domain, ".") === false) {
    return false;
  }

  // IDN 转 punycode，确保 DNS 查询可用
  if (function_exists("idn_to_ascii")) {
    $ascii = idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
    if ($ascii) {
      $domain = $ascii;
    }
  }

  // 性能优化：原先最多做 NS+A+AAAA+MX 四次串行 DNS 查询，未注册域名会
  // 依次全部超时，导致"0.13s 查询"后页面仍要多等好几秒。收敛为两次：
  //   1) NS —— 最强信号：域名注册并委派后必有权威 NS 记录
  //   2) A  —— 兜底：极少数未委派 NS 但已解析的域名
  // 这样可将最坏耗时减半，而对绝大多数域名判断结果不变。
  if (@checkdnsrr($domain, "NS")) {
    return true;
  }
  if (@checkdnsrr($domain, "A")) {
    return true;
  }

  return false;
}

/**
 * 拉取域名的实际 DNS 记录（NS / A / AAAA / MX），用于在 WHOIS/RDAP
 * 查不到详情、只能靠 DNS 兜底判定"已注册"时，向用户展示尽量多的有用信息
 * （名称服务器、解析 IP、邮件服务器），而不是只显示一句"已被注册"。
 *
 * 性能：此函数仅在已确认域名"在用/已注册"后才调用（见 index.php），
 * 因此不会拖慢"可注册"域名的检测热路径。每类记录单独查询并各自兜底，
 * 任一类型失败都不影响其余结果。
 *
 * @return array{ns:string[],a:string[],aaaa:string[],mx:string[]}
 */
function domainDnsRecords($domain)
{
  $empty = ["ns" => [], "a" => [], "aaaa" => [], "mx" => []];
  if (!$domain || strpos($domain, ".") === false) {
    return $empty;
  }

  if (function_exists("idn_to_ascii")) {
    $ascii = idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
    if ($ascii) {
      $domain = $ascii;
    }
  }

  $out = $empty;

  // NS：最能说明"已注册并委派"的记录，也可据此识别 DNS 提供商
  $ns = @dns_get_record($domain, DNS_NS);
  if (is_array($ns)) {
    foreach ($ns as $r) {
      if (!empty($r["target"])) {
        $out["ns"][] = rtrim(strtolower($r["target"]), ".");
      }
    }
  }

  // A / AAAA：正在解析的 IP（网站/服务在线）
  $a = @dns_get_record($domain, DNS_A);
  if (is_array($a)) {
    foreach ($a as $r) {
      if (!empty($r["ip"])) {
        $out["a"][] = $r["ip"];
      }
    }
  }
  $aaaa = @dns_get_record($domain, DNS_AAAA);
  if (is_array($aaaa)) {
    foreach ($aaaa as $r) {
      if (!empty($r["ipv6"])) {
        $out["aaaa"][] = $r["ipv6"];
      }
    }
  }

  // MX：邮件服务器（收发邮件），按优先级排序
  $mx = @dns_get_record($domain, DNS_MX);
  if (is_array($mx)) {
    usort($mx, fn($x, $y) => ($x["pri"] ?? 0) <=> ($y["pri"] ?? 0));
    foreach ($mx as $r) {
      if (!empty($r["target"])) {
        $out["mx"][] = rtrim(strtolower($r["target"]), ".");
      }
    }
  }

  // 去重并限制数量，避免异常数据撑爆界面
  foreach ($out as $k => $list) {
    $out[$k] = array_slice(array_values(array_unique($list)), 0, 8);
  }

  return $out;
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
