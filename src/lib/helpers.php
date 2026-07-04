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
