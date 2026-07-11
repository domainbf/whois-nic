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
    // 溢价检测脚本：仅当页面渲染了"可注册"域名的溢价槽位时加载。
    // 该脚本会异步请求 /premium 并在命中溢价时展示金色徽章+价格。
    $loadPremium = !empty($domain) && empty($multiMode) && empty($error)
      && isset($parser) && !$parser->registered && !$parser->reserved && !$parser->prohibited
      && !$parser->unknown && empty($dnsActive);
    if ($loadPremium):
  ?>
    <script src="<?= htmlspecialchars(asset('public/js/premium.js'), ENT_QUOTES, 'UTF-8'); ?>" defer></script>
  <?php endif; ?>
  <?php if ($fetchBeiAn): ?>
    <script src="public/js/beian.js" defer></script>
  <?php endif; ?>
