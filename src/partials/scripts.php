  <script src="public/js/app.js" defer></script>
  <script src="public/js/history.js" defer></script>

  <?php if (!empty($multiMode) && empty($embed)): ?>
    <script src="<?= htmlspecialchars(asset('public/js/multi.js'), ENT_QUOTES, 'UTF-8'); ?>" defer></script>
  <?php endif; ?>

  <?php if ($whoisData || $rdapData): ?>
    <script src="public/js/dates.js" defer></script>
    <script src="public/js/popper.min.js" defer></script>
    <script src="public/js/tippy-bundle.umd.min.js" defer></script>
    <script src="public/js/linkify.min.js" defer></script>
    <script src="public/js/linkify-html.min.js" defer></script>
    <script src="public/js/prism.js" defer></script>
    <script src="public/js/enhance.js" defer></script>
  <?php endif; ?>
  <?php if ($fetchPrices): ?>
    <script src="public/js/price.js" defer></script>
  <?php endif; ?>
  <?php
    // 溢价检测脚本：对"已注册"与"可注册"的单域名查询都加载。
    // 溢价域名（如 b.tools）既可能已注册、也可能待注册，二者都需检测并接管价格。
    // 排除保留 / 禁止 / 未知 / 无效等无价格意义的状态。
    $loadPremium = !empty($domain) && empty($multiMode) && empty($error)
      && isset($parser) && !$parser->reserved && !$parser->prohibited && !$parser->unknown;
    if ($loadPremium):
  ?>
    <script>window.__nwPremiumActive = true;</script>
    <script src="<?= htmlspecialchars(asset('public/js/premium.js'), ENT_QUOTES, 'UTF-8'); ?>" defer></script>
  <?php endif; ?>
  <?php if ($fetchBeiAn): ?>
    <script src="public/js/beian.js" defer></script>
  <?php endif; ?>
