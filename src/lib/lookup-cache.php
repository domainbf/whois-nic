<?php

/**
 * WHOIS / RDAP 查询结果的本地缓存（服务端文件缓存）
 *
 * 目的：
 *   1) 避免重复查询浪费资源——同一域名在短时间内被反复查询时（分享链接、
 *      爬虫预取、用户刷新），直接命中缓存返回，毫秒级响应，且不再对注册局
 *      WHOIS/RDAP 服务器发起请求，显著降低被限流/封禁的风险。
 *   2) 优化冷启动——Vercel Serverless 的容器在"热"状态下会复用 /tmp，
 *      因此同一容器后续请求可命中缓存；配合较长 TTL，跨请求收益明显。
 *
 * 设计要点：
 *   - 仅用 sys_get_temp_dir()（Serverless 下唯一可写目录），与 price.php 一致。
 *   - 缓存 key = 归一化域名 + 数据源组合的哈希，避免 whois/rdap 不同组合串味。
 *   - 分级 TTL：已注册域名信息稳定，缓存较久；未注册/未知变化快，缓存很短；
 *     错误结果不缓存（下次请求应重试）。
 *   - 用 serialize 持久化整包结果（Parser 为纯数据对象，安全可序列化）。
 *   - 全程 @ 静默 + 异常兜底：缓存永远是"锦上添花"，任何失败都不能影响查询主流程。
 */

// 分级 TTL（秒）
define("LOOKUP_CACHE_TTL_REGISTERED", 21600);   // 已注册：6 小时
define("LOOKUP_CACHE_TTL_UNREGISTERED", 900);   // 未注册/可注册：15 分钟
define("LOOKUP_CACHE_TTL_RESERVED", 86400);     // 保留/禁止：24 小时（几乎不变）
define("LOOKUP_CACHE_VERSION", "v2");           // 结构变更时递增，天然失效旧缓存（v2：ccTLD 解析修复 + BOM 清洗）

/**
 * 计算缓存文件路径。
 */
function lookup_cache_path($domain, array $dataSource)
{
  $dir = sys_get_temp_dir() . "/nw_lookup_cache";
  sort($dataSource); // 组合无序化，["whois","rdap"] 与 ["rdap","whois"] 视为同一
  $key = LOOKUP_CACHE_VERSION . "|" . strtolower($domain) . "|" . implode(",", $dataSource);
  return [$dir, $dir . "/" . sha1($key) . ".cache"];
}

/**
 * 读取新鲜缓存。命中返回解包后的数组，未命中/过期/损坏返回 null。
 *
 * 返回结构：
 *   ["domain"=>..,"whoisData"=>..,"rdapData"=>..,"parser"=>Parser,"dnsActive"=>bool]
 */
function lookup_cache_get($domain, array $dataSource)
{
  try {
    [, $file] = lookup_cache_path($domain, $dataSource);
    if (!is_file($file)) {
      return null;
    }
    $raw = @file_get_contents($file);
    if ($raw === false || $raw === "") {
      return null;
    }
    $entry = @unserialize($raw, ["allowed_classes" => ["Parser", "ParserRDAP"]]);
    if (!is_array($entry) || !isset($entry["expires"], $entry["payload"])) {
      return null;
    }
    // 过期：删除并视为未命中
    if (time() >= $entry["expires"]) {
      @unlink($file);
      return null;
    }
    $payload = $entry["payload"];
    if (!is_array($payload) || !isset($payload["parser"]) || !($payload["parser"] instanceof Parser)) {
      return null;
    }
    return $payload;
  } catch (Throwable $t) {
    return null; // 缓存读取失败绝不影响主流程
  }
}

/**
 * 写入缓存。仅缓存"确定性结果"（已注册/未注册/保留），不缓存错误。
 *
 * @param array $payload lookup_cache_get 返回的同构数组
 */
function lookup_cache_set($domain, array $dataSource, array $payload)
{
  try {
    if (empty($payload["parser"]) || !($payload["parser"] instanceof Parser)) {
      return;
    }
    $parser = $payload["parser"];

    // 分级 TTL
    if ($parser->reserved || $parser->prohibited) {
      $ttl = LOOKUP_CACHE_TTL_RESERVED;
    } elseif ($parser->registered) {
      $ttl = LOOKUP_CACHE_TTL_REGISTERED;
    } else {
      // 未注册/未知：短缓存（含 DNS 兜底判定为在用的情况也短缓存，尽快反映变化）
      $ttl = LOOKUP_CACHE_TTL_UNREGISTERED;
    }

    [$dir, $file] = lookup_cache_path($domain, $dataSource);
    if (!is_dir($dir)) {
      @mkdir($dir, 0777, true);
    }
    if (!is_dir($dir)) {
      return;
    }

    $entry = [
      "expires" => time() + $ttl,
      "payload" => $payload,
    ];
    @file_put_contents($file, serialize($entry), LOCK_EX);

    // 轻量 GC：目录文件过多时清理最旧的一批，防止 /tmp 无限增长
    lookup_cache_gc($dir);
  } catch (Throwable $t) {
    // 忽略写入失败
  }
}

/**
 * 简易垃圾回收：缓存条目超过阈值时，按修改时间删除最旧的约 1/4。
 */
function lookup_cache_gc($dir, $maxEntries = 500)
{
  try {
    $files = @glob($dir . "/*.cache");
    if (!is_array($files) || count($files) <= $maxEntries) {
      return;
    }
    usort($files, function ($a, $b) {
      return @filemtime($a) <=> @filemtime($b);
    });
    $removeCount = (int) ceil(count($files) / 4);
    for ($i = 0; $i < $removeCount; $i++) {
      @unlink($files[$i]);
    }
  } catch (Throwable $t) {
    // 忽略
  }
}
