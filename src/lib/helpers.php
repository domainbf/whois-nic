<?php

// 自动加载器与请求辅助函数（从 index.php 抽离，行为保持不变）

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

  // NS 是最强信号：域名一旦被注册并委派，就会有权威 NS 记录
  if (@checkdnsrr($domain, "NS")) {
    return true;
  }

  // 退而求其次：存在解析/邮件记录也说明域名在用
  foreach (["A", "AAAA", "MX"] as $type) {
    if (@checkdnsrr($domain, $type)) {
      return true;
    }
  }

  return false;
}

/**
 * 为搜索联想返回域名的 DNS 状态：是否已注册、是否已建站。
 *
 * - registered：存在 NS（已委派）或 A/AAAA/MX（在用）→ 视为已注册
 * - site：存在 A / AAAA 记录 → 视为已建站（前端据此展示 favicon）
 *
 * @return array{registered:bool, site:bool}
 */
function domainDnsStatus($domain)
{
  $result = ["registered" => false, "site" => false];

  if (!$domain || strpos($domain, ".") === false) {
    return $result;
  }

  if (function_exists("idn_to_ascii")) {
    $ascii = idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
    if ($ascii) {
      $domain = $ascii;
    }
  }

  // NS 是最快、最权威的"是否已注册"信号：
  // 已注册域名基本都有 NS 委派；未注册域名会返回 NXDOMAIN（很快）。
  if (@checkdnsrr($domain, "NS")) {
    $result["registered"] = true;
    // 仅对已注册域名再查 A（用于判断"是否已建站" → 前端展示 favicon）
    if (@checkdnsrr($domain, "A") || @checkdnsrr($domain, "AAAA")) {
      $result["site"] = true;
    }
    return $result;
  }

  // 极少数已注册但未委派 NS 的域名：用 A / MX 兜底一次
  if (@checkdnsrr($domain, "A")) {
    $result["registered"] = true;
    $result["site"] = true;
  } elseif (@checkdnsrr($domain, "MX")) {
    $result["registered"] = true;
  }

  return $result;
}

/**
 * 带缓存的 DNS 状态查询：避免每次联想都重新做 DNS 连接。
 *
 * 缓存写入 /tmp（Vercel PHP 运行时可写），按域名单文件存储。
 * TTL 策略——已注册域名变动很慢，可长时间缓存；未注册域名
 * 随时可能被注册，缓存较短：
 *   - 已注册：12 小时
 *   - 未注册：30 分钟
 *
 * @return array{registered:bool, site:bool}
 */
function domainDnsStatusCached($domain)
{
  $key = strtolower(trim($domain));
  if ($key === "" || strpos($key, ".") === false) {
    return ["registered" => false, "site" => false];
  }

  $dir = sys_get_temp_dir() . "/nw-dns-cache";
  $file = $dir . "/" . sha1($key) . ".json";

  // 命中未过期缓存 → 直接返回
  if (is_file($file)) {
    $raw = @file_get_contents($file);
    if ($raw !== false) {
      $data = json_decode($raw, true);
      if (is_array($data) && isset($data["exp"]) && $data["exp"] > time()) {
        return [
          "registered" => !empty($data["registered"]),
          "site" => !empty($data["site"]),
        ];
      }
    }
  }

  // 未命中 → 实时查询并写回缓存
  $status = domainDnsStatus($key);
  $ttl = $status["registered"] ? 12 * 3600 : 30 * 60;

  if (!is_dir($dir)) {
    @mkdir($dir, 0777, true);
  }
  @file_put_contents(
    $file,
    json_encode([
      "registered" => $status["registered"],
      "site" => $status["site"],
      "exp" => time() + $ttl,
    ]),
    LOCK_EX
  );

  return $status;
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
