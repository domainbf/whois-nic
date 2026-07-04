  <script src="public/js/app.js" defer></script>
  <script src="public/js/history.js" defer></script>
  <!-- 已取消输入框的后缀联想与注册状态检测：不再加载 tlds.js / autocomplete.js -->

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
  <?php if ($fetchBeiAn): ?>
    <script src="public/js/beian.js" defer></script>
  <?php endif; ?>
