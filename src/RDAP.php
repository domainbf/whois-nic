<?php
class RDAP
{
  public $domain;

  public $extension;

  private $servers;

  private $server;

  private const SERVERS_IANA = __DIR__ . "/data/rdap-servers-iana.json";

  private const SERVERS_EXTRA = __DIR__ . "/data/rdap-servers-extra.json";

  public function __construct($domain, $extension, $extensionTop)
  {
    $this->domain = $domain;
    $this->extension = $extension;

    $this->servers = $this->getServers();

    if (!empty($extensionTop) && !array_key_exists($extension, $this->servers)) {
      $this->extension = $extensionTop;
    }

    $server = $_GET["rdap-server"] ?? "";
    if ($server) {
      $this->server = $server;
    } else {
      $this->server = $this->getServer();
    }
  }

  private function getServers()
  {
    $servers = [];

    if (
      file_exists(self::SERVERS_IANA) &&
      ($json = file_get_contents(self::SERVERS_IANA)) !== false
    ) {
      $decoded = json_decode($json, true);
      if (is_array($decoded)) {
        foreach ($decoded["services"] as $service) {
          $tlds = $service[0];
          $server = $service[1][0];

          foreach ($tlds as $tld) {
            $servers[$tld] = $server;
          }
        }
      }
    }

    if (
      file_exists(self::SERVERS_EXTRA) &&
      ($json = file_get_contents(self::SERVERS_EXTRA)) !== false
    ) {
      $decoded = json_decode($json, true);
      if (is_array($decoded)) {
        $servers = array_merge($servers, $decoded);
      }
    }

    return $servers;
  }

  private function getServer()
  {
    if ($this->extension === "iana") {
      return "https://rdap.iana.org/";
    }

    $server = $this->servers[idn_to_ascii($this->extension)] ?? "";

    if (empty($server)) {
      throw new RuntimeException("No RDAP server found for '$this->domain'");
    }

    return $server;
  }

  // 目标 URL，供单源与并行路径共用。
  public function getURL()
  {
    return "{$this->server}domain/{$this->domain}";
  }

  // 构建配置好的 curl easy handle（不执行），供 getData 与并行 curl_multi 共用。
  public function buildHandle()
  {
    $curl = curl_init($this->getURL());

    curl_setopt_array($curl, [
      CURLOPT_RETURNTRANSFER => true,
      // 总超时 8s：RDAP 为 HTTPS 接口，正常应答均在 1~2s 内，8s 足够慢速
      // 注册局应答，又能让无接口/死服务器更快失败、加快结果展示。
      CURLOPT_TIMEOUT => 8,
      // 更快失败：连接阶段超时单独限制，避免死服务器拖慢整体查询
      CURLOPT_CONNECTTIMEOUT => 4,
      // RDAP 引导服务器常以 30x 重定向到权威服务器，跟随重定向以提升准确性
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_MAXREDIRS => 5,
      // 启用压缩传输，减少数据量、加快响应
      CURLOPT_ENCODING => "",
      CURLOPT_HTTPHEADER => [
        "Accept: application/rdap+json, application/json",
      ],
      CURLOPT_USERAGENT => "Mozilla/5.0 (compatible; WhoisLookup/1.0; +https://whois)",
    ]);

    return $curl;
  }

  // 归一化一个已执行的 handle 的结果为 [code, response]。
  // 非 JSON 内容视为空响应（部分死服务器返回 HTML 错误页）。
  public function finalizeHandle($curl, $response)
  {
    $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);

    if (!preg_match("/^application\/(rdap\+)?json/i", (string) $contentType)) {
      $response = "";
    }

    return [(int) $code, $response];
  }

  // HTTP 状态码是否为“可重试的瞬时故障”（限流 / 服务端错误）。
  public static function isTransientCode($code)
  {
    return $code === 429 || ($code >= 500 && $code <= 599);
  }

  public function getData()
  {
    // 瞬时故障（连接失败 / 限流 / 5xx）自动重试一次，消除偶发失败。
    // 正常成功或确定性失败（如 404 未注册）不会触发重试，不拖慢查询。
    $attempts = 0;
    $lastError = "";

    while ($attempts < 2) {
      $attempts++;

      $curl = $this->buildHandle();
      $response = curl_exec($curl);

      if ($response === false) {
        $lastError = curl_error($curl) ?: "RDAP 请求失败";
        curl_close($curl);
        // 连接类错误：短暂退避后重试
        if ($attempts < 2) {
          usleep(200000); // 200ms
          continue;
        }
        throw new RuntimeException($lastError);
      }

      [$code, $normalized] = $this->finalizeHandle($curl, $response);
      curl_close($curl);

      // 服务端瞬时错误：重试一次
      if (self::isTransientCode($code) && $attempts < 2) {
        usleep(200000);
        continue;
      }

      return [$code, $normalized];
    }

    throw new RuntimeException($lastError ?: "RDAP 请求失败");
  }
}
