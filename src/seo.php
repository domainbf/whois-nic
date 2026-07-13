<?php
// robots.txt 与 sitemap.xml 处理器：强化搜索引擎抓取与收录（SEO）。
// 通过路由 /robots.txt 与 /sitemap.xml 访问（见 vercel.json rewrites）。

require_once __DIR__ . "/../config/config.php";

// 计算站点绝对来源（scheme://host），供 sitemap/robots 使用绝对 URL
$scheme =
  (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ||
  (($_SERVER["HTTP_X_FORWARDED_PROTO"] ?? "") === "https")
    ? "https"
    : "http";
$host = $_SERVER["HTTP_HOST"] ?? "localhost";
$origin = $scheme . "://" . $host;

$path = parse_url($_SERVER["REQUEST_URI"] ?? "/", PHP_URL_PATH);

if ($path === "/sitemap.xml") {
  header("Content-Type: application/xml; charset=UTF-8");
  header("Cache-Control: public, max-age=86400");
  $today = date("Y-m-d");
  echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
  echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
  echo "  <url>\n";
  echo "    <loc>" . htmlspecialchars($origin . "/", ENT_QUOTES) . "</loc>\n";
  echo "    <lastmod>{$today}</lastmod>\n";
  echo "    <changefreq>daily</changefreq>\n";
  echo "    <priority>1.0</priority>\n";
  echo "  </url>\n";
  echo "</urlset>\n";
  return;
}

// 默认：robots.txt
header("Content-Type: text/plain; charset=UTF-8");
header("Cache-Control: public, max-age=86400");
echo "User-agent: *\n";
echo "Allow: /\n";
// 避免抓取带查询参数的重复内容与内部端点
echo "Disallow: /api/\n";
echo "Disallow: /login\n";
echo "Disallow: /*?*\n";
echo "\n";
echo "Sitemap: " . $origin . "/sitemap.xml\n";
