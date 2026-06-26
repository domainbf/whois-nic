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

    // 到期剩余颜色
    if ($remSec !== null && $remSec <= 0) { $remColor = 'nw-text-bad'; }
    elseif ($remSec !== null && $remSec <= 30 * 24 * 60 * 60) { $remColor = 'nw-text-bad'; }
    elseif ($remSec !== null && $remSec <= 60 * 24 * 60 * 60) { $remColor = 'nw-text-warn'; }
    else { $remColor = 'nw-text-ok'; }

    // EPP 状态码 → 颜色点
    $eppColor = function (string $code): string {
      $c = strtolower($code);
      if (strpos($c, 'prohibited') !== false) return '#f59e0b';
      if (strpos($c, 'pending') !== false || strpos($c, 'hold') !== false ||
          strpos($c, 'redemption') !== false || strpos($c, 'delete') !== false) return '#ef4444';
      if ($c === 'ok' || $c === 'active') return '#10b981';
      return '#71717a';
    };

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

    $displayDomain = $parser->domain ?: $domain;
  ?>
  <section class="nw-result">

    <!-- 顶部价格 / 年龄标签行 -->
    <?php if ($parser->age || $fetchPrices || $fetchBeiAn): ?>
      <div class="nw-pills">
        <?php if ($parser->age): ?>
          <span class="nw-pill nw-pill-accent" id="age" data-seconds="<?= $parser->ageSeconds; ?>">
            <svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <span>已注册 <?= htmlspecialchars($parser->age, ENT_QUOTES, 'UTF-8'); ?></span>
          </span>
        <?php endif; ?>
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

    <div class="nw-grid">
      <!-- ============ 左列 ============ -->
      <div class="nw-col-main">

        <!-- 域名主卡 -->
        <div class="nw-card nw-domain-card">
          <div class="nw-globe" aria-hidden="true">
            <svg viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="1">
              <circle cx="50" cy="50" r="45"/>
              <ellipse cx="50" cy="50" rx="20" ry="45"/>
              <ellipse cx="50" cy="50" rx="45" ry="20"/>
              <line x1="5" y1="50" x2="95" y2="50"/>
              <line x1="50" y1="5" x2="50" y2="95"/>
            </svg>
          </div>

          <div class="nw-domain-inner">
            <span class="nw-badge">DOMAIN</span>
            <h1 class="nw-domain-name">
              <a href="http://<?= htmlspecialchars($displayDomain, ENT_QUOTES, 'UTF-8'); ?>" rel="nofollow noopener noreferrer" target="_blank"><?= htmlspecialchars($displayDomain, ENT_QUOTES, 'UTF-8'); ?></a>
            </h1>
            <?php if ($parser->registrar): ?>
              <p class="nw-domain-sub">
                注册商：
                <?php if ($registrarLink): ?>
                  <a href="<?= htmlspecialchars($registrarLink, ENT_QUOTES, 'UTF-8'); ?>" rel="nofollow noopener noreferrer" target="_blank"><?= htmlspecialchars($parser->registrar, ENT_QUOTES, 'UTF-8'); ?></a>
                <?php else: ?>
                  <?= htmlspecialchars($parser->registrar, ENT_QUOTES, 'UTF-8'); ?>
                <?php endif; ?>
              </p>
            <?php endif; ?>

            <div class="nw-status-row">
              <span class="nw-status-badge nw-status-<?= $statusKey; ?>">
                <span class="nw-dot"></span><?= $statusLabel; ?>
              </span>
            </div>

            <?php if ($parser->creationDate || $parser->expirationDate || $parser->updatedDate || $parser->availableDate): ?>
              <div class="nw-dates">
                <?php if ($parser->creationDate): ?>
                  <div class="nw-date">
                    <p class="nw-date-label">创建日期</p>
                    <p class="nw-date-value" <?= $parser->creationDateISO8601 ? 'id="creation-date" data-iso8601="' . htmlspecialchars($parser->creationDateISO8601, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>><?= htmlspecialchars($parser->creationDate, ENT_QUOTES, 'UTF-8'); ?></p>
                  </div>
                <?php endif; ?>
                <?php if ($parser->expirationDate): ?>
                  <div class="nw-date">
                    <p class="nw-date-label">到期日期</p>
                    <p class="nw-date-value" <?= $parser->expirationDateISO8601 ? 'id="expiration-date" data-iso8601="' . htmlspecialchars($parser->expirationDateISO8601, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>><?= htmlspecialchars($parser->expirationDate, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php if ($parser->remaining): ?>
                      <p class="nw-date-sub <?= $remColor; ?>"><?= $remSec !== null && $remSec <= 0 ? '已过期' : '剩余 ' . htmlspecialchars($parser->remaining, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
                <?php if ($parser->updatedDate): ?>
                  <div class="nw-date">
                    <p class="nw-date-label">更新日期</p>
                    <p class="nw-date-value" <?= $parser->updatedDateISO8601 ? 'id="updated-date" data-iso8601="' . htmlspecialchars($parser->updatedDateISO8601, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>><?= htmlspecialchars($parser->updatedDate, ENT_QUOTES, 'UTF-8'); ?></p>
                  </div>
                <?php endif; ?>
                <?php if ($parser->availableDate): ?>
                  <div class="nw-date">
                    <p class="nw-date-label">可用日期</p>
                    <p class="nw-date-value" <?= $parser->availableDateISO8601 ? 'id="available-date" data-iso8601="' . htmlspecialchars($parser->availableDateISO8601, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>><?= htmlspecialchars($parser->availableDate, ENT_QUOTES, 'UTF-8'); ?></p>
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
              <div class="nw-registrar-logo"><?= htmlspecialchars(mb_substr($parser->registrar, 0, 1), ENT_QUOTES, 'UTF-8'); ?></div>
              <div class="nw-registrar-meta">
                <p class="nw-registrar-name"><?= htmlspecialchars($parser->registrar, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php if ($registrarLink): ?>
                  <a class="nw-registrar-url" href="<?= htmlspecialchars($registrarLink, ENT_QUOTES, 'UTF-8'); ?>" rel="nofollow noopener noreferrer" target="_blank"><?= htmlspecialchars($registrarLink, ENT_QUOTES, 'UTF-8'); ?></a>
                <?php endif; ?>
              </div>
            </div>
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
