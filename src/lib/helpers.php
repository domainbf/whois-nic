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
  // status: "registered" | "available" | "unknown"
  // registered/site 保留布尔，兼容前端旧字段
  $result = ["registered" => false, "site" => false, "status" => "unknown"];

  if (!$domain || strpos($domain, ".") === false) {
    return $result;
  }

  if (function_exists("idn_to_ascii")) {
    $ascii = idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
    if ($ascii) {
      $domain = $ascii;
    }
  }

  // NS 是最快、最权威的"是否已注册"正向信号：
  // 已注册且已委派的域名都有 NS。命中即可确定"已注册"。
  if (@checkdnsrr($domain, "NS")) {
    $result["registered"] = true;
    $result["status"] = "registered";
    if (@checkdnsrr($domain, "A") || @checkdnsrr($domain, "AAAA")) {
      $result["site"] = true;
    }
    return $result;
  }

  // 极少数已注册但未委派 NS 的域名：用 A / MX 兜底
  if (@checkdnsrr($domain, "A")) {
    $result["registered"] = true;
    $result["site"] = true;
    $result["status"] = "registered";
    return $result;
  }
  if (@checkdnsrr($domain, "MX")) {
    $result["registered"] = true;
    $result["status"] = "registered";
    return $result;
  }

  // 关键修复：DNS 无任何记录 ≠ 未注册。
  // 很多已注册域名（尤其单字符/溢价域名，如 u.com、u.net）被停放、
  // 未委派 NS，DNS 探测会漏判为"未注册"。此时用 RDAP 权威确认：
  //   HTTP 200 → 已注册；404 → 确实未注册；其它/失败 → 未知（不谎报未注册）。
  $rdap = domainRdapRegistered($domain);
  if ($rdap === true) {
    $result["registered"] = true;
    $result["status"] = "registered";
  } elseif ($rdap === false) {
    $result["status"] = "available";
  } else {
    $result["status"] = "unknown";
  }

  return $result;
}

/**
 * 用 RDAP 权威判断域名是否已注册（供 DNS 无记录时二次确认）。
 *
 * @return bool|null true=已注册, false=未注册, null=无法确定（无 RDAP 服务器/超时/异常）
 */
function domainRdapRegistered($domain)
{
  try {
    $pslPath = __DIR__ . "/../data/public-suffix-list.dat";
    if (!is_file($pslPath) || !class_exists("Pdp\\Rules")) {
      return null;
    }

    $rules = \Pdp\Rules::fromPath($pslPath);
    $d = \Pdp\Domain::fromIDNA2008($domain);

    $registrable = $domain;
    $extension = null;
    $extensionTop = null;
    try {
      $dn = $rules->getPrivateDomain($d);
      $registrable = $dn->registrableDomain()->toString();
      $extension = $dn->suffix()->toString();
    } catch (Throwable $t) {
      $dn = $rules->getICANNDomain($d);
      $registrable = $dn->registrableDomain()->toString();
      $extension = $dn->suffix()->toString();
      $extensionTop = $dn->domain()->label(0);
    }

    $rdap = new RDAP($registrable, $extension, $extensionTop);
    [$code, $response] = $rdap->getData(true); // 快速模式（短超时）

    if ($code === 200) {
      return true;
    }
    if ($code === 404) {
      return false;
    }
    return null; // 429/5xx/其它 → 无法确定
  } catch (Throwable $t) {
    // 无 RDAP 服务器（多数 ccTLD）或网络异常 → 无法确定
    return null;
  }
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
    return ["registered" => false, "site" => false, "status" => "unknown"];
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
          // 兼容旧缓存（无 status 字段）：按 registered 推导
          "status" => $data["status"] ?? (!empty($data["registered"]) ? "registered" : "available"),
        ];
      }
    }
  }

  // 未命中 → 实时查询并写回缓存
  $status = domainDnsStatus($key);
  // TTL：已注册变动慢，长缓存；未注册可能随时被抢注，短缓存；
  //      未知（查询失败）只缓存很短时间，尽快重试。
  if ($status["status"] === "registered") {
    $ttl = 12 * 3600;
  } elseif ($status["status"] === "available") {
    $ttl = 30 * 60;
  } else {
    $ttl = 5 * 60;
  }

  if (!is_dir($dir)) {
    @mkdir($dir, 0777, true);
  }
  @file_put_contents(
    $file,
    json_encode([
      "registered" => $status["registered"],
      "site" => $status["site"],
      "status" => $status["status"],
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
