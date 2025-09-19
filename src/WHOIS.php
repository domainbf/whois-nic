<?php
class WHOIS
{
  public $domain;

  public $extension;

  private $servers;

  private $server;

  private const SERVERS_IANA = __DIR__ . "/data/whois-servers-iana.json";

  private const SERVERS_EXTRA = __DIR__ . "/data/whois-servers-extra.json";

  public function __construct($domain, $extension, $extensionTop)
  {
    $this->domain = $domain;
    $this->extension = $extension;

    $this->servers = $this->getServers();

    if (!empty($extensionTop) && !array_key_exists($extension, $this->servers)) {
      $this->extension = $extensionTop;
    }

    $server = $_GET["whois-server"] ?? "";
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
        $servers = array_merge($servers, $decoded);
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
      return "whois.iana.org";
    }

    $server = $this->servers[idn_to_ascii($this->extension)] ?? "";

    if (empty($server) && !in_array($this->extension, WHOISWeb::EXTENSIONS)) {
      throw new RuntimeException("未找到 WHOIS 服务器 '$this->domain'");
    }

    return $server;
  }

  public function getData()
  {
    if (in_array($this->extension, WHOISWeb::EXTENSIONS)) {
      return (new WHOISWeb($this->domain, $this->extension))->getData();
    }

    $domain = idn_to_ascii($this->domain);

    $host = $this->server;
    $query = "$domain\r\n";

    if (is_array($this->server)) {
      $host = $this->server["host"];
      $query = str_replace("{domain}", $domain, $this->server["query"]);
    }

    $socket = @stream_socket_client("tcp://$host:43", $errno, $errstr, 10);

    if (!$socket) {
      throw new RuntimeException($errstr);
    }

    stream_set_timeout($socket, 10);

    fwrite($socket, $query);

    $data = stream_get_contents($socket);

    $encoding = mb_detect_encoding($data, ["UTF-8", "ISO-8859-1"], true);
    if ($encoding && $encoding !== "UTF-8") {
      $data = mb_convert_encoding($data, "UTF-8", $encoding);
    }

    $metaData = stream_get_meta_data($socket);
    if ($metaData["timed_out"]) {
      fclose($socket);
      throw new RuntimeException("查询超时，请稍后重试！");
    }

    fclose($socket);

    return $data;
  }
}
