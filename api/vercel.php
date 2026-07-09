<?php
$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

if ($path === "/robots.txt" || $path === "/sitemap.xml") {
  require_once __DIR__ . "/../src/seo.php";
} else if ($path === "/manifest") {
  require_once __DIR__ . "/../src/manifest.php";
} else if ($path === "/price") {
  require_once __DIR__ . "/../src/price.php";
} else if ($path === "/login") {
  require_once __DIR__ . "/../src/login.php";
} else {
  require_once __DIR__ . "/../src/index.php";
}
