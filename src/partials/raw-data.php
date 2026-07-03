<?php
  // 已注册域名的原始数据已并入 result.php 的右侧栏；此处仅处理未注册情形。
  // 错误类状态（无效 / 保留 / 禁止 / 未找到）上方已有说明卡片，原始 WHOIS 往往是
  // 服务器报错或限流文本（如 "Server can't process your request"），属噪声，予以隐藏。
  $rawErrorStates = ['invalid', 'unknown', 'reserved', 'prohibited'];
  $suppressRaw = in_array($resultState ?? '', $rawErrorStates, true);
?>
    <?php if (!$parser->registered && !$suppressRaw && ($whoisData || $rdapData)): ?>
      <?php if ($whoisData && $rdapData): ?>
        <section class="data-source">
          <div class="segmented">
            <button class="segmented-item segmented-item-selected" id="data-source-whois" type="button">WHOIS</button>
            <button class="segmented-item" id="data-source-rdap" type="button">RDAP</button>
          </div>
        </section>
      <?php endif; ?>
      <section class="raw-data">
        <?php if ($whoisData): ?>
          <div class="raw-data-container">
            <pre class="raw-data-whois" id="raw-data-whois" tabindex="0"><?= htmlspecialchars($whoisData, ENT_QUOTES, 'UTF-8'); ?></pre>
          </div>
        <?php endif; ?>
        <?php if ($rdapData): ?>
          <div class="raw-data-container">
            <pre class="raw-data-rdap" id="raw-data-rdap"><code class="language-json"><?= htmlspecialchars($rdapData, ENT_QUOTES, 'UTF-8'); ?></code></pre>
          </div>
        <?php endif; ?>
      </section>
    <?php endif; ?>
