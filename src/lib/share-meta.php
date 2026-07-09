<?php

// 分享 / SEO / Manifest 元数据计算（从 index.php 抽离）
// 依赖：$domain, $error, $parser
// 产出：$manifestHref, $currentUrl, $canonicalUrl, $siteOrigin,
//       $shareImage, $shareImageWidth, $shareImageHeight, $shareImageAlt,
//       $pageTitle, $shareTitle, $shareDescription, $jsonLd

$manifestHref = "manifest";
if ($_SERVER["QUERY_STRING"] ?? "") {
  $manifestHref .= "?" . htmlspecialchars($_SERVER["QUERY_STRING"], ENT_QUOTES, "UTF-8");
}

// 站点源（scheme://host）——社交爬虫（微信/Telegram/Facebook/Twitter 等）不解析
// <base> 且要求绝对 URL，因此所有对外分享用到的链接/图片都必须带上完整源。
$scheme = (
  (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
  (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
) ? "https" : "http";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$siteOrigin = $scheme . "://" . $host;

$currentUrl = $siteOrigin . ($_SERVER['REQUEST_URI'] ?? '/');

// 规范链接（canonical）：优先使用"/域名"的干净路径，避免 ?domain=、跟踪参数、
// 大小写等造成的重复 URL 稀释 SEO 权重；无域名时指向站点根。
if (!empty($domain)) {
  $canonicalUrl = $siteOrigin . "/" . rawurlencode($domain);
} else {
  $canonicalUrl = $siteOrigin . "/";
}

// 将 BASE 下的相对资源转为绝对 URL（BASE 通常为 "/"）
$absAsset = function (string $path) use ($siteOrigin) {
  $base = rtrim(BASE, "/");
  return $siteOrigin . $base . "/" . ltrim($path, "/");
};

// 默认分享图：专为社交卡片制作的 1024×1024 品牌封面（绝对 URL）
$shareImage = $absAsset("public/images/og-cover.png");
$shareImageWidth = 1024;
$shareImageHeight = 1024;
$shareImageAlt = SITE_TITLE;

if ($domain) {
  if ($error) {
    $shareTitle = "$domain | 无效域名查询";
    $shareDescription = "查询的域名 '$domain' 是无效的。请尝试其他域名。";
  } elseif ($parser->reserved || $parser->prohibited) {
    $shareTitle = "$domain | 保留或限制注册";
    $shareDescription = "域名 '$domain' 已被注册局保留或禁止/限制注册。";
  } elseif ($parser->unknown) {
    $shareTitle = "$domain | 未找到";
    $shareDescription = "未找到域名 '$domain' 的注册信息。";
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
    $shareImage = $absAsset("public/images/available_domain.png");
    $shareImageWidth = 335;
    $shareImageHeight = 368;
    $shareImageAlt = "$domain 可注册";
  }
  $shareImageAlt = $shareImageAlt ?: $shareTitle;
} else {
  $shareTitle = SITE_TITLE;
  $shareDescription = SITE_DESCRIPTION;
}

// SEO 页面标题：有域名时组合"域名 状态 | 站点名"，信息更丰富、更利于点击与收录；
// 无域名时用"站点名 · 副标题"强化品牌与关键词。
if ($domain) {
  $pageTitle = $shareTitle . " · " . SITE_TITLE;
} else {
  $pageTitle = SITE_TITLE . " · " . SITE_SHORT_TITLE;
}

// 结构化数据（JSON-LD）：帮助搜索引擎理解页面。首页输出带站内搜索的 WebSite，
// 域名页输出面包屑 + 结果概要，提升富媒体检索表现。
$jsonLdItems = [];
$jsonLdItems[] = [
  "@context" => "https://schema.org",
  "@type" => "WebSite",
  "name" => SITE_TITLE,
  "url" => $siteOrigin . "/",
  "description" => SITE_DESCRIPTION,
  "potentialAction" => [
    "@type" => "SearchAction",
    "target" => [
      "@type" => "EntryPoint",
      "urlTemplate" => $siteOrigin . "/{search_term_string}",
    ],
    "query-input" => "required name=search_term_string",
  ],
];
if ($domain && !$error) {
  $jsonLdItems[] = [
    "@context" => "https://schema.org",
    "@type" => "BreadcrumbList",
    "itemListElement" => [
      ["@type" => "ListItem", "position" => 1, "name" => SITE_TITLE, "item" => $siteOrigin . "/"],
      ["@type" => "ListItem", "position" => 2, "name" => $domain, "item" => $canonicalUrl],
    ],
  ];
}
$jsonLd = json_encode(
  count($jsonLdItems) === 1 ? $jsonLdItems[0] : $jsonLdItems,
  JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
