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

  // 该后缀是否走 HTTP 抓取（WHOISWeb）而非 43 端口 socket。
  // 并行查询时据此判断能否并入 socket 事件循环。
  public function isWeb()
  {
    return in_array($this->extension, WHOISWeb::EXTENSIONS);
  }

  public function getData()
  {
    if ($this->isWeb()) {
      return (new WHOISWeb($this->domain, $this->extension))->getData();
    }

    // 单源查询：打开 socket 后阻塞式跑完“空闲超时读取”循环。
    $socket = $this->openSocket();
    return $this->readAll($socket);
  }

  // 打开到 registry 43 端口的连接、写出查询，返回“非阻塞”socket。
  // 供单源查询与并行查询共用：并行时由 Lookup 在统一事件循环里增量读取。
  //
  // 连接超时收紧到 6s：部分 registry 的 43 端口较慢或限流，
  // 过长的超时会让“查询加载”卡住数秒，6s 足够正常应答又能快速失败回退。
  public function openSocket()
  {
    $domain = idn_to_ascii($this->domain);

    $host = $this->server;
    $query = "$domain\r\n";

    if (is_array($this->server)) {
      $host = $this->server["host"];
      $query = str_replace("{domain}", $domain, $this->server["query"]);
    }

    // 瞬时连接故障（连接被重置 / registry 限流）自动重试一次，消除偶发失败。
    $socket = @stream_socket_client("tcp://$host:43", $errno, $errstr, 6);
    if (!$socket) {
      usleep(200000); // 200ms 退避
      $socket = @stream_socket_client("tcp://$host:43", $errno, $errstr, 6);
    }

    if (!$socket) {
      throw new RuntimeException($errstr);
    }

    // 先以阻塞方式写出查询（域名很短，瞬间完成），随后转非阻塞以便多路复用读取。
    fwrite($socket, $query);
    stream_set_blocking($socket, false);

    return $socket;
  }

  // 单源查询用：阻塞式跑完“非阻塞 + 空闲超时读取”循环，返回解码后的完整应答。
  //
  // 许多国别 registry（如 .ug / .ai）在发送完应答后不会主动关闭 43 端口连接，
  // 传统的 stream_get_contents() 会一路阻塞到硬超时，才是“同一后缀有的秒回、
  // 有的卡满数秒”的根因（例如 www.ug 秒回，而 u.ug 卡到 6s 超时）。
  // 这里改为：一旦收到数据、且连续空闲超过 $idleTimeout，即判定应答结束，
  // 把这类慢查询从固定 6s 降到“数据到达 + 1.5s”，同时绝不截断仍在传输的数据。
  private function readAll($socket)
  {
    $data = "";
    $idleTimeout = 1.5;                  // 收到数据后的最大空闲等待（秒）
    $hardDeadline = microtime(true) + 6; // 整体读取硬上限，兜底无响应/极慢服务器
    $lastActivity = microtime(true);
    $timedOut = false;

    while (true) {
      if (microtime(true) >= $hardDeadline) {
        // 完全没收到任何数据才算真正超时；已有数据则视为读取足够，正常返回
        $timedOut = ($data === "");
        break;
      }

      $read = [$socket];
      $write = null;
      $except = null;
      // 200ms 一轮，兼顾响应速度与 CPU 占用
      $ready = @stream_select($read, $write, $except, 0, 200000);

      if ($ready === false) {
        break; // select 出错，停止读取
      }

      if ($ready > 0) {
        $chunk = fread($socket, 8192);
        if ($chunk === false) {
          break;
        }
        if ($chunk !== "") {
          $data .= $chunk;
          $lastActivity = microtime(true);
        }
        if (feof($socket)) {
          break; // 对端已关闭连接：正常结束（快速服务器走这条路径）
        }
      } else {
        // 本轮无数据可读：已收到数据且空闲超过阈值，认定应答完成
        if ($data !== "" && (microtime(true) - $lastActivity) >= $idleTimeout) {
          break;
        }
      }
    }

    fclose($socket);

    if ($timedOut) {
      throw new RuntimeException("查询超时，请稍后重试！");
    }

    return $this->finalizeData($data);
  }

  // 编码归一：部分 registry 以 ISO-8859-1 返回，统一转成 UTF-8。
  // 公开以便并行路径在自行读取完 socket 后复用同一套解码逻辑。
  public function finalizeData($data)
  {
    $encoding = mb_detect_encoding($data, ["UTF-8", "ISO-8859-1"], true);
    if ($encoding && $encoding !== "UTF-8") {
      $data = mb_convert_encoding($data, "UTF-8", $encoding);
    }

    return $data;
  }
}
