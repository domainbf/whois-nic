<?php require_once __DIR__ . "/../lib/icons.php"; ?>
<?php if ($parser->registered): ?>
  <?php
    require_once __DIR__ . "/../lib/registrar-map.php";
    $statusMapping = require __DIR__ . "/../lib/status-map.php";
    $registrarLink = $parser->registrar ? ($parser->registrarURL ?: registrar_website($parser->registrar)) : "";

    // 域名状态 → 颜色（活跃 / 即将到期 / 已过期）
    $remSec = $parser->remainingSeconds;
    if ($remSec !== null && $remSec <= 0) {
      $statusKey = 'expired'; $statusLabel = '已过期';
    } elseif ($remSec !== null && $remSec <= 60 * 24 * 60 * 60) {
      $statusKey = 'expiring'; $statusLabel = '即将到期';
    } elseif ($remSec !== null) {
      $statusKey = 'active'; $statusLabel = '活跃';
    } else {
      $statusKey = 'neutral'; $statusLabel = '已注册';
    }

    // 到期剩余颜色 + 中文剩余文案
    if ($remSec !== null && $remSec <= 0) { $remColor = 'nw-text-bad'; }
    elseif ($remSec !== null && $remSec <= 30 * 24 * 60 * 60) { $remColor = 'nw-text-bad'; }
    elseif ($remSec !== null && $remSec <= 60 * 24 * 60 * 60) { $remColor = 'nw-text-warn'; }
    else { $remColor = 'nw-text-ok'; }

    $remText = '';
    if ($remSec !== null) {
      $remText = $remSec <= 0 ? '已过期' : '剩余 ' . intval(ceil($remSec / 86400)) . ' 天';
    }

    // 域龄（年）→ 状态行小药丸
    $ageYears = '';
    if ($parser->ageSeconds !== null && $parser->ageSeconds > 0) {
      $y = floor($parser->ageSeconds / (365.25 * 86400));
      $ageYears = $y < 1 ? '<1 年' : intval($y) . ' 年';
    }

    // 日期：YYYY-MM-DD + 中文相对时间
    $isoDate = function ($iso, $fallback = '') {
      if ($iso) { $t = strtotime($iso); if ($t) return date('Y-m-d', $t); }
      return $fallback;
    };
    $relPast = function ($iso) {
      if (!$iso) return '';
      $t = strtotime($iso); if (!$t) return '';
      $d = abs(time() - $t); $day = 86400;
      if ($d < $day) return '今天';
      $years = floor($d / (365.25 * $day));
      if ($years >= 1) return intval($years) . '年前';
      $months = floor($d / (30.4 * $day));
      if ($months >= 1) return intval($months) . '个月前';
      return intval($d / $day) . '天前';
    };

    // 从原始 WHOIS 文本提取扩展字段（注册局 ID / WHOIS 服务器 / 注册人 / 滥用联系）
    $wRaw = $whoisData ?: '';
    // 冒号后只允许同一行内的空格/制表符（[ \t]），值必须以非空白字符起始，
    // 避免字段为空时把后续行（如下一标签或 Domain Status）误当成值。
    $grab = function ($labels) use ($wRaw) {
      foreach ((array) $labels as $lb) {
        if (preg_match('/^[ \t]*' . preg_quote($lb, '/') . '[ \t]*:[ \t]*(\S.*?)[ \t]*\r?$/mi', $wRaw, $m)) {
          $v = trim($m[1]);
          if ($v !== '' && !preg_match('/redact|privacy|not disclosed|data protected|gdpr|statutory masking/i', $v)) {
            return $v;
          }
        }
      }
      return '';
    };
    // 邮箱必须含 @ 且不含空白；电话必须含数字且不含 @，否则视为解析噪声丢弃
    $cleanEmail = function ($v) {
      return ($v !== '' && strpos($v, '@') !== false && !preg_match('/\s/', $v)) ? $v : '';
    };
    $cleanPhone = function ($v) {
      return ($v !== '' && preg_match('/\d/', $v) && strpos($v, '@') === false) ? $v : '';
    };
    $registryDomainId = $grab('Registry Domain ID');
    $whoisServerVal   = $grab(['Registrar WHOIS Server', 'WHOIS Server']);
    $registrarIanaId  = $grab(['Registrar IANA ID', 'IANA ID', 'Sponsoring Registrar IANA ID']);
    // 注册商地址：拼接街道 / 城市 / 省州 / 邮编 / 国家（任一存在即显示）
    $registrarAddrParts = array_filter([
      $grab(['Registrar Street', 'Registrar Address']),
      $grab('Registrar City'),
      $grab(['Registrar State/Province', 'Registrar Province']),
      $grab(['Registrar Postal Code', 'Registrar Postal']),
      $grab('Registrar Country'),
    ], function ($v) { return $v !== ''; });
    $registrarAddress = implode(' · ', $registrarAddrParts);
    $registrantEmail  = $cleanEmail($grab(['Registrant Email', 'Registrant Contact Email']));
    $registrantPhone  = $cleanPhone($grab('Registrant Phone'));
    $abuseEmail       = $cleanEmail($grab('Registrar Abuse Contact Email'));
    $abusePhone       = $cleanPhone($grab('Registrar Abuse Contact Phone'));

    // RDAP 结构化兜底：薄注册局 / RDAP-first 的 gTLD，IANA ID、注册商地址、滥用联系
    // 往往不在原始 WHOIS 文本里，而在 RDAP 实体（registrar entity）的结构化字段中。
    // 这里在不改动后端解析器的前提下，从 RDAP JSON 补全缺失字段，提升识别准确率。
    $rdapJson = $rdapData ? json_decode($rdapData, true) : null;
    if (is_array($rdapJson) && !empty($rdapJson['entities'])) {
      // 递归查找指定 role 的实体（registrar 顶层、abuse 常为其子实体）
      $findEntity = function ($entities, $role) use (&$findEntity) {
        if (!is_array($entities)) return null;
        foreach ($entities as $e) {
          if (!is_array($e)) continue;
          $roles = $e['roles'] ?? [];
          if ((is_array($roles) && in_array($role, $roles, true)) || $roles === $role) {
            return $e;
          }
          if (!empty($e['entities'])) {
            $sub = $findEntity($e['entities'], $role);
            if ($sub) return $sub;
          }
        }
        return null;
      };
      $vcardEls = function ($entity) {
        if (empty($entity['vcardArray'])) return [];
        return $entity['vcardArray']['elements'] ?? ($entity['vcardArray'][1] ?? []);
      };
      $vcardGet = function ($els, $key) {
        if (!is_array($els)) return null;
        foreach ($els as $it) {
          if (is_array($it) && ($it[0] ?? '') === $key) return $it;
        }
        return null;
      };
      $registrarEntity = $findEntity($rdapJson['entities'], 'registrar');
      if ($registrarEntity) {
        // 注册商名称兜底（WHOIS 缺失时用 RDAP vcard fn/org）
        if ($parser->registrar === '') {
          $fn = $vcardGet($vcardEls($registrarEntity), 'fn') ?: $vcardGet($vcardEls($registrarEntity), 'org');
          if ($fn && !empty($fn[3]) && is_string($fn[3])) {
            $parser->registrar = trim($fn[3]);
            if ($registrarLink === '') {
              $registrarLink = $parser->registrarURL ?: registrar_website($parser->registrar);
            }
          }
        }
        // Registrar IANA ID（publicIds，type 含 "IANA"）
        if ($registrarIanaId === '' && !empty($registrarEntity['publicIds'])) {
          foreach ($registrarEntity['publicIds'] as $pid) {
            if (isset($pid['identifier']) && preg_match('/iana/i', $pid['type'] ?? '')) {
              $registrarIanaId = trim((string) $pid['identifier']);
              break;
            }
          }
        }
        // 注册商地址（vcard adr：优先 label 参数，其次 7 段结构化数组）
        if ($registrarAddress === '') {
          $adr = $vcardGet($vcardEls($registrarEntity), 'adr');
          if ($adr) {
            if (!empty($adr[1]['label']) && is_string($adr[1]['label'])) {
              $registrarAddress = trim(preg_replace('/\s*\R\s*/', ' · ', $adr[1]['label']));
            } elseif (isset($adr[3]) && is_array($adr[3])) {
              $registrarAddress = implode(' · ', array_filter(
                array_map(fn($v) => is_string($v) ? trim($v) : '', $adr[3]),
                fn($v) => $v !== ''
              ));
            }
          }
        }
        // 滥用联系（registrar 的 abuse 子实体 vcard email/tel）
        if ($abuseEmail === '' || $abusePhone === '') {
          $abuseEntity = !empty($registrarEntity['entities'])
            ? $findEntity($registrarEntity['entities'], 'abuse')
            : null;
          if ($abuseEntity) {
            $aEls = $vcardEls($abuseEntity);
            if ($abuseEmail === '') {
              $em = $vcardGet($aEls, 'email');
              if ($em && isset($em[3])) $abuseEmail = $cleanEmail(trim((string) $em[3]));
            }
            if ($abusePhone === '') {
              $tel = $vcardGet($aEls, 'tel');
              if ($tel && isset($tel[3])) $abusePhone = $cleanPhone(trim((string) $tel[3]));
            }
          }
        }
      }
    }

    $mailLink = function ($val) {
      if (strpos($val, '@') !== false) return 'mailto:' . $val;
      if (preg_match('#^https?://#i', $val)) return $val;
      return '';
    };

    $hasRegistrant = $registrantEmail || $registrantPhone;
    $hasAbuse      = $abuseEmail || $abusePhone;
    $hasRegTech    = $whoisServerVal || $registryDomainId || $registrarIanaId || $registrarAddress;
    $dataSourceLabel = $whoisData ? 'whois' : ($rdapData ? 'rdap' : '');

    // NS 提供商识别（用于右侧小徽标）
    $nsBrand = function (string $ns): string {
      $n = strtolower($ns);
      $map = [
        'cloudflare' => 'Cloudflare', 'awsdns' => 'AWS', 'amazonaws' => 'AWS',
        'azure-dns' => 'Azure', 'googledomains' => 'Google', 'google' => 'Google',
        'dnspod' => 'DNSPod', 'alidns' => '阿里云', 'aliyun' => '阿里云',
        'godaddy' => 'GoDaddy', 'domaincontrol' => 'GoDaddy', 'namecheap' => 'Namecheap',
        'registrar-servers' => 'Namecheap', 'vercel-dns' => 'Vercel', 'name-services' => 'eNom',
        'dnsowl' => 'NameSilo', 'nsone' => 'NS1', 'ns.cloudflare' => 'Cloudflare',
        'hichina' => '阿里云', 'he.net' => 'HE', 'digitalocean' => 'DigitalOcean',
      ];
      foreach ($map as $k => $v) { if (strpos($n, $k) !== false) return $v; }
      return '';
    };

    // EPP 状态码 → 颜色点
    $eppColor = function (string $code): string {
      $c = strtolower($code);
      if (strpos($c, 'prohibited') !== false) return '#f59e0b';
      if (strpos($c, 'pending') !== false || strpos($c, 'hold') !== false ||
          strpos($c, 'redemption') !== false || strpos($c, 'delete') !== false) return '#ef4444';
      if ($c === 'ok' || $c === 'active') return '#10b981';
      return '#71717a';
    };

    $displayDomain = $parser->domain ?: $domain;
    $registrarInitial = $parser->registrar ? mb_substr($parser->registrar, 0, 1) : '';
  ?>
  <section class="nw-result">
    <div class="nw-grid">
      <!-- ============ 左列 ============ -->
      <div class="nw-col-main">

        <!-- 域名主卡 -->
        <div class="nw-card nw-domain-card">
          <div class="nw-globe" aria-hidden="true">
            <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
              <defs>
                <clipPath id="nw-globe-clip"><circle cx="50" cy="50" r="46"/></clipPath>
              </defs>
              <circle cx="50" cy="50" r="46" class="nw-globe-sphere"/>
              <g clip-path="url(#nw-globe-clip)">
                <!-- 纬线（静止，构成地平参考） -->
                <g class="nw-globe-grid">
                  <line x1="4" y1="50" x2="96" y2="50"/>
                  <line x1="9" y1="29" x2="91" y2="29"/>
                  <line x1="9" y1="71" x2="91" y2="71"/>
                  <line x1="16" y1="14" x2="84" y2="14"/>
                  <line x1="16" y1="86" x2="84" y2="86"/>
                </g>
                <!-- 经线（rx 周期变化，模拟地球绕竖轴自转） -->
                <g class="nw-globe-grid nw-globe-meridians">
                  <ellipse cx="50" cy="50" rx="46" ry="46">
                    <animate attributeName="rx" values="46;0;46" keyTimes="0;0.5;1" dur="8s" repeatCount="indefinite" />
                  </ellipse>
                  <ellipse cx="50" cy="50" rx="32" ry="46">
                    <animate attributeName="rx" values="46;0;46" keyTimes="0;0.5;1" begin="-2s" dur="8s" repeatCount="indefinite" />
                  </ellipse>
                  <ellipse cx="50" cy="50" rx="0" ry="46">
                    <animate attributeName="rx" values="46;0;46" keyTimes="0;0.5;1" begin="-4s" dur="8s" repeatCount="indefinite" />
                  </ellipse>
                  <ellipse cx="50" cy="50" rx="32" ry="46">
                    <animate attributeName="rx" values="46;0;46" keyTimes="0;0.5;1" begin="-6s" dur="8s" repeatCount="indefinite" />
                  </ellipse>
                </g>
              </g>
              <circle cx="50" cy="50" r="46" class="nw-globe-ring"/>
            </svg>
          </div>

          <div class="nw-domain-inner">
            <span class="nw-badge">DOMAIN</span>
            <h1 class="nw-domain-name">
              <a href="http://<?= htmlspecialchars($displayDomain, ENT_QUOTES, 'UTF-8'); ?>" rel="nofollow noopener noreferrer" target="_blank"><?= htmlspecialchars($displayDomain, ENT_QUOTES, 'UTF-8'); ?></a>
            </h1>

            <!-- 状态行：状态徽章 + 域龄药丸 -->
            <div class="nw-status-row">
              <span class="nw-status-badge nw-status-<?= $statusKey; ?>">
                <span class="nw-dot"></span><?= $statusLabel; ?>
              </span>
              <?php if ($ageYears): ?>
                <span class="nw-age-pill" id="age" data-seconds="<?= $parser->ageSeconds; ?>">
                  <svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                  <?= htmlspecialchars($ageYears, ENT_QUOTES, 'UTF-8'); ?>
                </span>
              <?php endif; ?>
            </div>

            <!-- 价格 / 备案标签（由 JS 异步填充） -->
            <?php if ($fetchPrices || $fetchBeiAn): ?>
              <div class="nw-pills">
                <?php if ($fetchPrices): ?>
                  <span class="nw-price-slot" id="message-price" data-domain="<?= htmlspecialchars($domain ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    <span class="nw-skeleton"></span>
                  </span>
                <?php endif; ?>
                <?php if ($fetchBeiAn): ?>
                  <span class="nw-beian-slot" id="message-beian" data-domain="<?= htmlspecialchars($domain ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    <span class="nw-skeleton"></span>
                  </span>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <!-- 数据来源行：查询耗时 · 数据来源 -->
            <?php if ($dataSourceLabel): ?>
              <?php $elapsedText = (isset($queryElapsed) && $queryElapsed > 0) ? number_format($queryElapsed, 2) . 's · ' : ''; ?>
              <div class="nw-source-row">
                <span class="nw-source-label"><?= htmlspecialchars($elapsedText . $dataSourceLabel, ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
            <?php endif; ?>

            <!-- 日期网格 -->
            <?php if ($parser->creationDate || $parser->expirationDate || $parser->updatedDate || $parser->availableDate): ?>
              <div class="nw-dates">
                <?php if ($parser->creationDate): ?>
                  <div class="nw-date">
                    <p class="nw-date-label">创建日期</p>
                    <p class="nw-date-value" <?= $parser->creationDateISO8601 ? 'id="creation-date" data-iso8601="' . htmlspecialchars($parser->creationDateISO8601, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>><?= htmlspecialchars($isoDate($parser->creationDateISO8601, $parser->creationDate), ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php if ($relPast($parser->creationDateISO8601)): ?>
                      <p class="nw-date-sub"><?= htmlspecialchars($relPast($parser->creationDateISO8601), ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
                <?php if ($parser->expirationDate): ?>
                  <div class="nw-date">
                    <p class="nw-date-label">到期日期</p>
                    <p class="nw-date-value" <?= $parser->expirationDateISO8601 ? 'id="expiration-date" data-iso8601="' . htmlspecialchars($parser->expirationDateISO8601, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>><?= htmlspecialchars($isoDate($parser->expirationDateISO8601, $parser->expirationDate), ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php if ($remText): ?>
                      <p class="nw-date-sub <?= $remColor; ?>"><?= htmlspecialchars($remText, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
                <?php if ($parser->updatedDate): ?>
                  <div class="nw-date nw-date-wide">
                    <p class="nw-date-label">更新日期</p>
                    <p class="nw-date-value" <?= $parser->updatedDateISO8601 ? 'id="updated-date" data-iso8601="' . htmlspecialchars($parser->updatedDateISO8601, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>><?= htmlspecialchars($isoDate($parser->updatedDateISO8601, $parser->updatedDate), ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php if ($relPast($parser->updatedDateISO8601)): ?>
                      <p class="nw-date-sub"><?= htmlspecialchars($relPast($parser->updatedDateISO8601), ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
                <?php if ($parser->availableDate): ?>
                  <div class="nw-date">
                    <p class="nw-date-label">可用日期</p>
                    <p class="nw-date-value" <?= $parser->availableDateISO8601 ? 'id="available-date" data-iso8601="' . htmlspecialchars($parser->availableDateISO8601, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>><?= htmlspecialchars($isoDate($parser->availableDateISO8601, $parser->availableDate), ENT_QUOTES, 'UTF-8'); ?></p>
                  </div>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <!-- 注册人联系（主卡内） -->
            <?php if ($hasRegistrant): ?>
              <div class="nw-contact-grid">
                <?php if ($registrantEmail): ?>
                  <div class="nw-contact">
                    <p class="nw-date-label">注册人邮箱</p>
                    <?php $ml = $mailLink($registrantEmail); ?>
                    <?php if ($ml): ?>
                      <a class="nw-contact-value nw-link" href="<?= htmlspecialchars($ml, ENT_QUOTES, 'UTF-8'); ?>" rel="nofollow noopener noreferrer" target="_blank"><?= htmlspecialchars($registrantEmail, ENT_QUOTES, 'UTF-8'); ?></a>
                    <?php else: ?>
                      <p class="nw-contact-value"><?= htmlspecialchars($registrantEmail, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
                <?php if ($registrantPhone): ?>
                  <div class="nw-contact">
                    <p class="nw-date-label">注册人电话</p>
                    <p class="nw-contact-value"><?= htmlspecialchars($registrantPhone, ENT_QUOTES, 'UTF-8'); ?></p>
                  </div>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- 状态 + NS 双卡 -->
        <div class="nw-subgrid">
          <?php if ($parser->status): ?>
            <div class="nw-card nw-list-card">
              <h3 class="nw-card-title">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                域名状态
              </h3>
              <div class="nw-status-list">
                <?php foreach ($parser->status as $st):
                  $code = $st["text"];
                  $cn = $statusMapping[$code] ?? $code; ?>
                  <div class="nw-status-item">
                    <span class="nw-status-bullet" style="background-color: <?= $eppColor($code); ?>"></span>
                    <div class="nw-status-item-body">
                      <?php if ($st["url"]): ?>
                        <a class="nw-status-name" href="<?= htmlspecialchars($st["url"], ENT_QUOTES, 'UTF-8'); ?>" rel="nofollow noopener noreferrer" target="_blank"><?= htmlspecialchars($cn, ENT_QUOTES, 'UTF-8'); ?></a>
                      <?php else: ?>
                        <span class="nw-status-name"><?= htmlspecialchars($cn, ENT_QUOTES, 'UTF-8'); ?></span>
                      <?php endif; ?>
                      <p class="nw-status-code"><?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <?php if ($parser->nameServers): ?>
            <div class="nw-card nw-list-card">
              <h3 class="nw-card-title">
                <?= inline_icon('server'); ?>
                NS 服务器
              </h3>
              <div class="nw-ns-list">
                <?php foreach ($parser->nameServers as $ns): $brand = $nsBrand($ns); ?>
                  <div class="nw-ns-item">
                    <span class="nw-ns-dot"></span>
                    <span class="nw-ns-name"><?= htmlspecialchars($ns, ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php if ($brand): ?><span class="nw-ns-brand"><?= htmlspecialchars($brand, ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- ============ 右列 ============ -->
      <div class="nw-col-side">

        <!-- 注册商卡 -->
        <?php if ($parser->registrar): ?>
          <div class="nw-card nw-registrar-card">
            <div class="nw-registrar-head">
              <h3 class="nw-card-title nw-card-title-plain">注册商</h3>
            </div>
            <div class="nw-registrar-body">
              <div class="nw-registrar-logo"><?= htmlspecialchars($registrarInitial, ENT_QUOTES, 'UTF-8'); ?></div>
              <div class="nw-registrar-meta">
                <p class="nw-registrar-name"><?= htmlspecialchars($parser->registrar, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php if ($registrarLink): ?>
                  <a class="nw-registrar-url" href="<?= htmlspecialchars($registrarLink, ENT_QUOTES, 'UTF-8'); ?>" rel="nofollow noopener noreferrer" target="_blank"><?= htmlspecialchars($registrarLink, ENT_QUOTES, 'UTF-8'); ?></a>
                <?php endif; ?>
              </div>
            </div>

            <?php if ($hasRegTech): ?>
              <div class="nw-registrar-section">
                <?php if ($whoisServerVal): ?>
                  <div class="nw-kv">
                    <span class="nw-kv-key">WHOIS 服务器</span>
                    <span class="nw-kv-val"><?= htmlspecialchars($whoisServerVal, ENT_QUOTES, 'UTF-8'); ?></span>
                  </div>
                <?php endif; ?>
                <?php if ($registryDomainId): ?>
                  <div class="nw-kv">
                    <span class="nw-kv-key">注册局 ID</span>
                    <span class="nw-kv-val"><?= htmlspecialchars($registryDomainId, ENT_QUOTES, 'UTF-8'); ?></span>
                  </div>
                <?php endif; ?>
                <?php if ($registrarIanaId): ?>
                  <div class="nw-kv">
                    <span class="nw-kv-key">注册商 IANA ID</span>
                    <span class="nw-kv-val"><?= htmlspecialchars($registrarIanaId, ENT_QUOTES, 'UTF-8'); ?></span>
                  </div>
                <?php endif; ?>
                <?php if ($registrarAddress): ?>
                  <div class="nw-kv">
                    <span class="nw-kv-key">注册商地址</span>
                    <span class="nw-kv-val"><?= htmlspecialchars($registrarAddress, ENT_QUOTES, 'UTF-8'); ?></span>
                  </div>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <?php if ($hasAbuse): ?>
              <div class="nw-registrar-section">
                <p class="nw-section-title">滥用联系</p>
                <?php if ($abuseEmail): ?>
                  <div class="nw-kv">
                    <span class="nw-kv-key">邮箱</span>
                    <a class="nw-kv-val nw-link" href="<?= htmlspecialchars($mailLink($abuseEmail) ?: '#', ENT_QUOTES, 'UTF-8'); ?>" rel="nofollow noopener noreferrer" target="_blank"><?= htmlspecialchars($abuseEmail, ENT_QUOTES, 'UTF-8'); ?></a>
                  </div>
                <?php endif; ?>
                <?php if ($abusePhone): ?>
                  <div class="nw-kv">
                    <span class="nw-kv-key">电话</span>
                    <span class="nw-kv-val"><?= htmlspecialchars($abusePhone, ENT_QUOTES, 'UTF-8'); ?></span>
                  </div>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <?php if ($hasRegistrant): ?>
              <div class="nw-registrar-section">
                <p class="nw-section-title">注册人信息</p>
                <?php if ($registrantEmail): ?>
                  <div class="nw-kv">
                    <span class="nw-kv-key">邮箱</span>
                    <a class="nw-kv-val nw-link" href="<?= htmlspecialchars($mailLink($registrantEmail) ?: '#', ENT_QUOTES, 'UTF-8'); ?>" rel="nofollow noopener noreferrer" target="_blank"><?= htmlspecialchars($registrantEmail, ENT_QUOTES, 'UTF-8'); ?></a>
                  </div>
                <?php endif; ?>
                <?php if ($registrantPhone): ?>
                  <div class="nw-kv">
                    <span class="nw-kv-key">电话</span>
                    <span class="nw-kv-val"><?= htmlspecialchars($registrantPhone, ENT_QUOTES, 'UTF-8'); ?></span>
                  </div>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <!-- 原始数据面板 -->
        <?php if ($whoisData || $rdapData): ?>
          <div class="nw-card nw-raw-panel">
            <div class="nw-raw-head">
              <div class="nw-raw-tabs">
                <?php if ($whoisData): ?>
                  <button class="nw-raw-tab segmented-item-selected" id="data-source-whois" type="button">WHOIS</button>
                <?php endif; ?>
                <?php if ($rdapData): ?>
                  <button class="nw-raw-tab<?= $whoisData ? '' : ' segmented-item-selected'; ?>" id="data-source-rdap" type="button">RDAP</button>
                <?php endif; ?>
              </div>
              <div class="nw-raw-actions">
                <button class="nw-raw-action" id="raw-copy" type="button">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                  复制
                </button>
              </div>
            </div>
            <div class="nw-raw-body">
              <?php if ($whoisData): ?>
                <pre class="raw-data-whois" id="raw-data-whois" tabindex="0"><?= htmlspecialchars($whoisData, ENT_QUOTES, 'UTF-8'); ?></pre>
              <?php endif; ?>
              <?php if ($rdapData): ?>
                <pre class="raw-data-rdap" id="raw-data-rdap"<?= $whoisData ? ' style="display:none"' : ''; ?>><code class="language-json"><?= htmlspecialchars($rdapData, ENT_QUOTES, 'UTF-8'); ?></code></pre>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
<?php endif; ?>
