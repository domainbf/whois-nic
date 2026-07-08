<?php
  // 多域名批量查询结果。两种状态：
  //  1) $multiEntry：仅输入了暗号 0，尚未输入后缀 → 展示引导卡片
  //  2) $multiData ：已输入后缀 → 展示 34 个域名的紧凑列表（可点击进入详情）

  // 状态 → 展示配置（文案在 i18n）
  $multiStateMeta = [
    "available"  => ["dot" => "#16a34a", "key" => "multi_state_available"],
    "registered" => ["dot" => "#9ca3af", "key" => "multi_state_registered"],
    "unknown"    => ["dot" => "#d1d5db", "key" => "multi_state_unknown"],
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

    // 汇总计数
    $countAvailable = 0;
    $countRegistered = 0;
    foreach ($items as $it) {
      if ($it["state"] === "available") $countAvailable++;
      elseif ($it["state"] === "registered") $countRegistered++;
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
          <a class="nw-multi-link" href="<?= htmlspecialchars($detailHref, ENT_QUOTES, 'UTF-8'); ?>">
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
      <?= number_format($elapsed, 2); ?>s · <?= htmlspecialchars($source === 'rdap' ? 'rdap' : ($source === 'dns' ? 'dns' : '—'), ENT_QUOTES, 'UTF-8'); ?>
      <?php if ($source === 'dns'): ?>
        · <?= htmlspecialchars(t('multi_dns_note'), ENT_QUOTES, 'UTF-8'); ?>
      <?php endif; ?>
    </p>
  </section>

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
