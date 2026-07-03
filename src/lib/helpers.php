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
