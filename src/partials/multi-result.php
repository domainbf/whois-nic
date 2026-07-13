<?php
  // 多域名批量查询结果。两种状态：
  //  1) $multiEntry：仅输入了暗号 0，尚未输入后缀 → 展示引导卡片
  //  2) $multiData ：已输入后缀 → 展示 34 个域名的紧凑列表（可点击进入详情）

  // 状态 → 展示配置（文案在 i18n）
  // DoH/SSL 探测三态：可注册(绿)、已注册/在用(灰)、未知(琥珀，点击可精确核实)。
  $multiStateMeta = [
    "available"  => ["dot" => "#16a34a", "key" => "multi_state_available"],
    "registered" => ["dot" => "#9ca3af", "key" => "multi_state_registered"],
    "unknown"    => ["dot" => "#f59e0b", "key" => "multi_state_unknown"],
  ];
?>

<?php if (!empty($multiEntry)): ?>
  <!-- 模式入口：提示输入后缀 -->
  <section class="nw-multi" aria-live="polite">
    <div class="nw-multi-intro">
      <span class="nw-multi-intro-icon" aria-hidden="true">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
      </span>
      <h2 class="nw-multi-intro-title"><?= htmlspecialchars(t('multi_intro_title'), ENT_QUOTES, 'UTF-8'); ?></h2>
      <p class="nw-multi-intro-desc"><?= htmlspecialchars(t('multi_intro_desc'), ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
  </section>

<?php elseif (!empty($multiData) && !empty($multiData["items"])): ?>
  <?php
    $items = $multiData["items"];
    $suffix = $multiData["suffix"];
    $source = $multiData["source"];
    $elapsed = $multiData["elapsed"] ?? 0;

    // 汇总计数：可注册 / 已注册 / 未知（待点击核实）
    $countAvailable = 0;
    $countRegistered = 0;
    $countUnknown = 0;
    foreach ($items as $it) {
      if ($it["state"] === "available") $countAvailable++;
      elseif ($it["state"] === "registered") $countRegistered++;
      elseif ($it["state"] === "unknown") $countUnknown++;
    }
  ?>
  <section class="nw-multi">
    <!-- 概览行 -->
    <div class="nw-multi-head">
      <div class="nw-multi-head-title">
        <span class="nw-multi-suffix">.<?= htmlspecialchars($suffix, ENT_QUOTES, 'UTF-8'); ?></span>
        <span class="nw-multi-count"><?= count($items); ?> <?= htmlspecialchars(t('multi_domains_unit'), ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
      <div class="nw-multi-summary">
        <span class="nw-multi-sum-item"><span class="nw-status-bullet" style="background-color:#16a34a"></span><?= $countAvailable; ?> <?= htmlspecialchars(t('multi_state_available'), ENT_QUOTES, 'UTF-8'); ?></span>
        <span class="nw-multi-sum-item"><span class="nw-status-bullet" style="background-color:#9ca3af"></span><?= $countRegistered; ?> <?= htmlspecialchars(t('multi_state_registered'), ENT_QUOTES, 'UTF-8'); ?></span>
        <?php if ($countUnknown > 0): ?>
          <span class="nw-multi-sum-item"><span class="nw-status-bullet" style="background-color:#f59e0b"></span><?= $countUnknown; ?> <?= htmlspecialchars(t('multi_state_unknown'), ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endif; ?>
      </div>
    </div>

    <!-- 紧凑列表 -->
    <ul class="nw-multi-list">
      <?php foreach ($items as $item):
        $state = $item["state"];
        $meta = $multiStateMeta[$state] ?? $multiStateMeta["unknown"];
        $stateLabel = t($meta["key"]);
        $detailHref = BASE . "?domain=" . urlencode($item["domain"]);
      ?>
        <li class="nw-multi-row nw-multi-row--<?= htmlspecialchars($state, ENT_QUOTES, 'UTF-8'); ?>">
          <a class="nw-multi-link js-multi-detail" href="<?= htmlspecialchars($detailHref, ENT_QUOTES, 'UTF-8'); ?>" data-domain="<?= htmlspecialchars($item["domain"], ENT_QUOTES, 'UTF-8'); ?>">
            <span class="nw-multi-dot" style="background-color: <?= $meta["dot"]; ?>" aria-hidden="true"></span>
            <span class="nw-multi-domain">
              <span class="nw-multi-prefix"><?= htmlspecialchars($item["label"], ENT_QUOTES, 'UTF-8'); ?></span><span class="nw-multi-tld">.<?= htmlspecialchars($suffix, ENT_QUOTES, 'UTF-8'); ?></span>
            </span>
            <span class="nw-multi-state nw-multi-state--<?= htmlspecialchars($state, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($stateLabel, ENT_QUOTES, 'UTF-8'); ?></span>
            <svg class="nw-multi-arrow" width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M5.646 3.646a.5.5 0 0 1 .708 0l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L9.293 8 5.646 4.354a.5.5 0 0 1 0-.708"/></svg>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>

    <!-- 来源 / 耗时 -->
    <p class="nw-multi-foot">
      <?= number_format($elapsed, 2); ?>s · <?= htmlspecialchars($source, ENT_QUOTES, 'UTF-8'); ?>
      · <?= htmlspecialchars(t('multi_dns_note'), ENT_QUOTES, 'UTF-8'); ?>
    </p>
  </section>

  <!-- 详情弹窗：点击列表项时，用 JSON 接口拉取数据并在弹窗内原生渲染详情卡片 -->
  <div class="nw-modal" id="multi-modal" hidden aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="multi-modal-title">
    <div class="nw-modal-backdrop" data-close="1"></div>
    <div class="nw-modal-panel">
      <div class="nw-modal-head">
        <span class="nw-modal-title" id="multi-modal-title"></span>
        <a class="nw-modal-open" id="multi-modal-open" href="#" target="_blank" rel="noopener" title="<?= htmlspecialchars(t('multi_modal_open'), ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?= htmlspecialchars(t('multi_modal_open'), ENT_QUOTES, 'UTF-8'); ?>">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>
        </a>
        <button type="button" class="nw-modal-close" data-close="1" aria-label="<?= htmlspecialchars(t('multi_modal_close'), ENT_QUOTES, 'UTF-8'); ?>">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>
      <div class="nw-modal-body">
        <!-- 加载态 -->
        <div class="nw-modal-loading" id="multi-modal-loading">
          <span class="nw-modal-spinner" aria-hidden="true"></span>
          <span class="nw-modal-loading-text" id="multi-modal-loading-text"><?= htmlspecialchars(t('multi_modal_loading'), ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <!-- 错误态 -->
        <div class="nw-modal-error" id="multi-modal-error" hidden>
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <p class="nw-modal-error-text"><?= htmlspecialchars(t('multi_modal_error'), ENT_QUOTES, 'UTF-8'); ?></p>
          <div class="nw-modal-error-actions">
            <button type="button" class="nw-modal-btn" id="multi-modal-retry"><?= htmlspecialchars(t('multi_modal_retry'), ENT_QUOTES, 'UTF-8'); ?></button>
            <a class="nw-modal-btn nw-modal-btn--ghost" id="multi-modal-error-open" href="#" target="_blank" rel="noopener"><?= htmlspecialchars(t('multi_modal_open'), ENT_QUOTES, 'UTF-8'); ?></a>
          </div>
        </div>
        <!-- 结果态：由 JS 原生渲染详情卡片 -->
        <div class="nw-modal-content" id="multi-modal-content" hidden></div>
      </div>
    </div>
  </div>

  <!-- 详情卡片所需的本地化标签（供 multi.js 读取，随语言自动切换） -->
  <script type="application/json" id="multi-modal-labels"><?= json_encode([
    "available"      => t('multi_state_available'),
    "registeredState" => t('multi_state_registered'),
    "registrar"      => t('card_registrar'),
    "creation"       => t('date_creation'),
    "expiration"     => t('date_expiration'),
    "updated"        => t('date_updated'),
    "status"         => t('card_status'),
    "ns"             => t('card_ns'),
    "dnssec"         => t('card_dnssec'),
    "dnssecSigned"   => t('dnssec_signed'),
    "dnssecUnsigned" => t('dnssec_unsigned'),
    "age"            => t('card_age'),
    "remaining"      => t('card_remaining'),
    "full"           => t('multi_modal_full'),
    "empty"          => t('multi_modal_empty_field'),
  ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP); ?></script>

<?php else: ?>
  <!-- 无有效后缀 / 空结果 -->
  <section class="nw-multi">
    <div class="nw-multi-intro">
      <span class="nw-multi-intro-icon" aria-hidden="true">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
      </span>
      <h2 class="nw-multi-intro-title"><?= htmlspecialchars(t('multi_empty_title'), ENT_QUOTES, 'UTF-8'); ?></h2>
      <p class="nw-multi-intro-desc"><?= htmlspecialchars(t('multi_intro_desc'), ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
  </section>
<?php endif; ?>
