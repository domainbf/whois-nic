// Ensure headers are sent and content-type is set
header('Content-Type: text/html; charset=UTF-8');

// Define constants (replace with your actual values)
define('BASE', '/');
define('SITE_TITLE', '域名查询');
define('SITE_DESCRIPTION', '一个简单的域名查询工具');
define('SITE_KEYWORDS', '域名, WHOIS, RDAP, 查询');
$manifestHref = 'manifest.json';
$domain = isset($_GET['domain']) ? htmlspecialchars($_GET['domain'], ENT_QUOTES, 'UTF-8') : '';
$dataSource = ['whois', 'rdap']; // Default data sources
$fetchPrices = isset($_GET['prices']) && $_GET['prices'] == '1';
$error = false;
$parser = new stdClass();
$parser->unknown = false;
$parser->reserved = false;
$parser->registered = false;
$parser->registrar = null;
$parser->registrarURL = null;
$parser->creationDate = null;
$parser->creationDateISO8601 = null;
$parser->expirationDate = null;
$parser->expirationDateISO8601 = null;
$parser->updatedDate = null;
$parser->updatedDateISO8601 = null;
$parser->availableDate = null;
$parser->availableDateISO8601 = null;
$parser->status = [];
$parser->nameServers = [];
$parser->age = null;
$parser->ageSeconds = null;
$parser->remaining = null;
$parser->remainingSeconds = null;
$parser->pendingDelete = false;
$parser->gracePeriod = false;
$parser->redemptionPeriod = false;
$whoisData = null;
$rdapData = null;

// Simulate some data for testing (replace with actual logic)
if ($domain) {
    $parser->registered = true;
    $parser->registrar = 'Example Registrar';
    $parser->creationDate = '2023-01-15';
    $parser->creationDateISO8601 = '2023-01-15T12:00:00Z';
    $parser->expirationDate = '2026-01-15';
    $parser->expirationDateISO8601 = '2026-01-15T12:00:00Z';
    $parser->updatedDate = '2025-09-01';
    $parser->updatedDateISO8601 = '2025-09-01T14:30:00Z';
    $parser->nameServers = ['ns1.example.com', 'ns2.example.com'];
}

?>
<!DOCTYPE html>
<html lang="en-US">
<head>
  <base href="<?= BASE; ?>">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="theme-color" content="#e1f9f9">
  <meta name="description" content="<?= SITE_DESCRIPTION ?>">
  <meta name="keywords" content="<?= SITE_KEYWORDS ?>">
  <link rel="shortcut icon" href="public/favicon.ico">
  <link rel="icon" href="public/images/favicon.svg" type="image/svg+xml">
  <link rel="apple-touch-icon" href="public/images/apple-icon-180.png">
  <!-- (Splash screen links omitted for brevity, add back if needed) -->
  <link rel="manifest" href="<?= $manifestHref; ?>">
  <title><?= ($domain ? "$domain | " : "") . SITE_TITLE ?></title>
  <link rel="stylesheet" href="public/css/global.css">
  <link rel="stylesheet" href="public/css/index.css">
  <link rel="stylesheet" href="public/css/json.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:wght@300;400;500;600;700;900&display=swap">
  <style>
    /* CSS styles omitted for brevity, copy from original code */
    body {
      background-color: #ffffff;
      background-image: repeating-linear-gradient(0deg, transparent, transparent 19px, #eee 20px), repeating-linear-gradient(90deg, transparent, transparent 19px, #eee 20px);
      background-size: 20px 20px;
    }
    /* Add other styles as needed */
  </style>
</head>
<body>
  <header>
    <div>
      <h1>
        <?php if ($domain): ?>
          <a href="<?= BASE; ?>"><?= SITE_TITLE ?></a>
        <?php else: ?>
          <?= SITE_TITLE ?>
        <?php endif; ?>
      </h1>
      <form action="<?= BASE; ?>" id="form" method="get">
        <div class="search-box">
          <input autocapitalize="off" autocomplete="domain" autocorrect="off" <?= $domain ? "" : "autofocus"; ?> class="input search-input" id="domain" inputmode="url" name="domain" placeholder="示例：NIC.RW" required type="text" value="<?= $domain ?>">
          <button class="search-clear" id="domain-clear" type="button" aria-label="Clear">
            <svg width="1em" height="1em" viewBox="0 0 16 16" fill="currentColor"><path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/></svg>
          </button>
        </div>
        <button class="button search-button">
          <svg width="1em" height="1em" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" id="search-icon"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/></svg>
          <span>查询</span>
        </button>
        <div class="checkboxes">
          <div class="checkbox">
            <input <?= in_array("whois", $dataSource, true) ? "checked" : "" ?> class="checkbox-trigger" id="checkbox-whois" name="whois" type="checkbox" value="1">
            <label class="checkbox-label" for="checkbox-whois"><span class="checkbox-leading-icon"><svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true"><circle cx="9" cy="9" r="9" fill="#222"/><text x="9" y="13" text-anchor="middle" fill="#fff" font-size="12" font-family="Arial" font-weight="bold">W</text></svg></span>WHOIS</label>
            <div class="checkbox-icon-wrapper"><svg class="checkbox-icon checkbox-icon-checkmark" width="50" height="39.69" viewBox="0 0 50 39.69" aria-hidden="true"><path d="M43.68 0L16.74 27.051 6.319 16.63l-6.32 6.32 16.742 16.74L50 6.32z"/></svg></div>
          </div>
          <div class="checkbox">
            <input <?= in_array("rdap", $dataSource, true) ? "checked" : "" ?> class="checkbox-trigger" id="checkbox-rdap" name="rdap" type="checkbox" value="1">
            <label class="checkbox-label" for="checkbox-rdap"><span class="checkbox-leading-icon"><svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true"><circle cx="9" cy="9" r="9" fill="#222"/><text x="9" y="13" text-anchor="middle" fill="#fff" font-size="12" font-family="Arial" font-weight="bold">R</text></svg></span>RDAP</label>
            <div class="checkbox-icon-wrapper"><svg class="checkbox-icon checkbox-icon-checkmark" width="50" height="39.69" viewBox="0 0 50 39.69" aria-hidden="true"><path d="M43.68 0L16.74 27.051 6.319 16.63l-6.32 6.32 16.742 16.74L50 6.32z"/></svg></div>
          </div>
          <div class="checkbox">
            <input <?= $fetchPrices ? "checked" : "" ?> class="checkbox-trigger" id="checkbox-prices" name="prices" type="checkbox" value="1">
            <label class="checkbox-label" for="checkbox-prices"><span class="checkbox-leading-icon"><svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true"><circle cx="9" cy="9" r="9" fill="#222"/><text x="9" y="13" text-anchor="middle" fill="#fff" font-size="12" font-family="Arial" font-weight="bold">$</text></svg></span>价格</label>
            <div class="checkbox-icon-wrapper"><svg class="checkbox-icon checkbox-icon-checkmark" width="50" height="39.69" viewBox="0 0 50 39.69" aria-hidden="true"><path d="M43.68 0L16.74 27.051 6.319 16.63l-6.32 6.32 16.742 16.74L50 6.32z"/></svg></div>
          </div>
        </div>
      </form>
    </div>
  </header>
  <main>
    <?php if ($domain): ?>
      <section class="messages">
        <div>
          <?php if ($error): ?>
            <div class="message message-negative">
              <div class="message-data">
                <h2 class="message-title"><svg width="1em" height="1em" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" class="message-icon"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/><path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/></svg>'<?= $domain ?>' 这可不是有效的域名哦。</h2>
              </div>
            </div>
          <?php elseif ($parser->unknown): ?>
            <div class="message message-notice">
              <div class="message-data">
                <h2 class="message-title"><svg width="1em" height="1em" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" class="message-icon"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/><path d="M5.255 5.786a.237.237 0 0 0 .241.247h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286m1.557 5.763c0 .533.425.927 1.01.927.609 0 1.028-.394 1.028-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94"/></svg>'<?= $domain ?>' 暂无信息，请稍后重试。</h2>
              </div>
            </div>
          <?php elseif ($parser->reserved): ?>
            <div class="message message-notice">
              <div class="message-data">
                <h2 class="message-title"><svg width="1em" height="1em" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" class="message-icon"><path d="M15 8a6.97 6.97 0 0 0-1.71-4.584l-9.874 9.875A7 7 0 0 0 15 8M2.71 12.584l9.874-9.875a7 7 0 0 0-9.874 9.874ZM16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0"/></svg>'<?= $domain ?>' 该死，这个域名已被保留了。</h2>
              </div>
            </div>
          <?php elseif ($parser->registered): ?>
            <div class="message message-positive">
              <div class="message-data">
                <h1 class="message-title"><svg width="1em" height="1em" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" class="message-icon"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/><path d="m10.97 4.97-.02.022-3.473 4.425-2.093-2.094a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05"/></svg><a href="http://<?= $domain ?>" rel="nofollow noopener noreferrer" target="_blank"><?= $domain ?></a> 已被注册，查看以下信息吧。</h1>
                <?php if ($parser->registrar): ?>
                  <div class="message-label"><span class="message-icon-leading"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7m.5-3.5h-1a.5.5 0 0 1 0-1h1a.5.5 0 0 1 0 1m1-1a.5.5 0 0 1 .5-.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5zm-11 5a.5.5 0 0 1-.5-.5V1.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 .5.5V5h-1a.5.5 0 0 0-.5.5v3.5h-1a.5.5 0 0 1-.5-.5V1.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 .5.5V11a.5.5 0 0 1-.5.5h-2a.5.5 0 0 0-.5.5V13a.5.5 0 0 1-.5.5H2a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 .5.5V2.5a.5.5 0 0 0 .5.5h2a.5.5 0 0 0 .5-.5V1.5a.5.5 0 0 1 .5-.5h-7a.5.5 0 0 0-.5.5V13a.5.5 0 0 1-.5.5v2.5a.5.5 0 0 0 .5.5zm10-5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5z"/></svg></span>注册平台</div>
                  <div><?= htmlspecialchars($parser->registrar, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <?php if ($parser->creationDate): ?>
                  <div class="message-label"><span class="message-icon-leading"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M4.5 1a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1zm1 0h3a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5zm4.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5z"/><path d="M12 4H4a1 1 0 0 0-1 1v9a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V5a1 1 0 0 0-1-1zm-8 1h8v9H4V5z"/><path d="M8.5 8.5v2h-1v-2zm0-2h-1v2h1v-2zm0-2h-1v2h1v-2z"/></svg></span>创建日期</div>
                  <div><span id="creation-date" data-iso8601="<?= htmlspecialchars($parser->creationDateISO8601, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($parser->creationDate, ENT_QUOTES, 'UTF-8') ?></span></div>
                <?php endif; ?>
                <?php if ($parser->expirationDate): ?>
                  <div class="message-label"><span class="message-icon-leading"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M4.5 1a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1zm1 0h3a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5zm4.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5z"/><path d="M12 4H4a1 1 0 0 0-1 1v9a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V5a1 1 0 0 0-1-1zM4 5h8v9H4V5z"/><path d="M8.5 8.5v2h-1v-2zm0-2h-1v2h1v-2zm0-2h-1v2h1v-2z"/></svg></span>到期日期</div>
                  <div><span id="expiration-date" data-iso8601="<?= htmlspecialchars($parser->expirationDateISO8601, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($parser->expirationDate, ENT_QUOTES, 'UTF-8') ?></span></div>
                <?php endif; ?>
                <?php if ($parser->updatedDate): ?>
                  <div class="message-label"><span class="message-icon-leading"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M4 14a1 1 0 0 1-1-1V1a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1zm8-1v-1H4v1zm-8-2h8V1H4v10zm-1-3a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 0 1h-8a.5.5 0 0 1-.5-.5zm0-3a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 0 1h-8a.5.5 0 0 1-.5-.5z"/><path d="M8 12a1 1 0 1 1 0-2 1 1 0 0 1 0 2zm0-3a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/></svg></span>更新日期</div>
                  <div><span id="updated-date" data-iso8601="<?= htmlspecialchars($parser->updatedDateISO8601, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($parser->updatedDate, ENT_QUOTES, 'UTF-8') ?></span></div>
                <?php endif; ?>
                <?php if ($parser->nameServers): ?>
                  <div class="message-label"><span class="message-icon-leading"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 10a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h5a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-5z"/><path d="M12.44 1.44a.5.5 0 0 1 .12.55l-2.49 11.55a.5.5 0 0 1-.95.06L7 8.355l-2.043 4.65a.5.5 0 0 1-.95-.06L1.44 2a.5.5 0 0 1 .55-.12L8 4.288l5.44-2.968z"/></svg></span>NS服务器</div>
                  <div class="message-value-name-servers"><?php foreach ($parser->nameServers as $nameServer): ?><div><?= htmlspecialchars($nameServer, ENT_QUOTES, 'UTF-8') ?></div><?php endforeach; ?></div>
                <?php endif; ?>
              </div>
            </div>
          <?php else: ?>
            <div class="message message-informative">
              <div class="message-data">
                <h2 class="message-title"><svg width="1em" height="1em" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" class="message-icon"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/><path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/></svg>'<?= $domain ?>' 这个域名似乎尚未注册，去申请试试吧。</h2>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </section>
    <?php endif; ?>
  </main>
  <button class="back-to-top" id="back-to-top">
    <svg width="1em" height="1em" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M8 12a.5.5 0 0 0 .5-.5V5.707l2.146 2.147a.5.5 0 0 0 .708-.708l-3-3a.5.5 0 0 0-.708 0l-3 3a.5.5 0 1 0 .708.708L7.5 5.707V11.5a.5.5 0 0 0 .5.5" fill-rule="evenodd"/></svg>
  </button>
  <script>
    window.addEventListener("DOMContentLoaded", function() {
      const domainElement = document.getElementById("domain");
      const domainClearElement = document.getElementById("domain-clear");
      if (domainElement && domainElement.value) domainClearElement.classList.add("visible");
      if (domainElement) domainElement.addEventListener("input", (e) => e.target.value ? domainClearElement.classList.add("visible") : domainClearElement.classList.remove("visible"));
      if (domainClearElement) domainClearElement.addEventListener("click", () => { if (domainElement) { domainElement.focus(); domainElement.select(); if (!document.execCommand("delete", false)) domainElement.setRangeText(""); domainClearElement.classList.remove("visible"); } });
      const checkboxNames = ["whois", "rdap", "prices"];
      checkboxNames.forEach((name) => { const checkbox = document.getElementById(`checkbox-${name}`); if (checkbox) localStorage.setItem(`checkbox-${name}`, +checkbox.checked); });
      const form = document.getElementById("form"); const searchIcon = document.getElementById("search-icon"); if (form && searchIcon) form.addEventListener("submit", () => searchIcon.classList.add("searching"));
      const backToTop = document.getElementById("back-to-top"); if (backToTop) { backToTop.addEventListener("click", () => window.scrollTo({ behavior: "smooth", top: 0 })); window.addEventListener("scroll", () => document.documentElement.scrollTop > 360 ? backToTop.classList.add("visible") : backToTop.classList.remove("visible")); }
    });
  </script>
  <script src="public/js/popper.min.js" defer></script>
  <script src="public/js/tippy-bundle.umd.min.js" defer></script>
  <script src="public/js/linkify.min.js" defer></script>
  <script src="public/js/linkify-html.min.js" defer></script>
  <script src="public/js/prism.js" defer></script>
  <script>
    window.addEventListener("DOMContentLoaded", function() {
      function updateDateElementText(elementId) { const element = document.getElementById(elementId); if (element) { const iso8601 = element.dataset.iso8601; if (iso8601) { const date = new Date(iso8601); const year = date.getFullYear(); const month = String(date.getMonth() + 1).padStart(2, "0"); const day = String(date.getDate()).padStart(2, "0"); element.innerText = `${year}年${month}月${day}日`; } } }
      updateDateElementText("creation-date"); updateDateElementText("expiration-date"); updateDateElementText("updated-date"); updateDateElementText("available-date");
      function updateDateElementTooltip(elementId) { const element = document.getElementById(elementId); if (element) { const iso8601 = element.dataset.iso8601; if (iso8601) { const date = new Date(iso8601); const hours = String(date.getHours()).padStart(2, "0"); const minutes = String(date.getMinutes()).padStart(2, "0"); const seconds = String(date.getSeconds()).padStart(2, "0"); const formattedTime = `${hours}时${minutes}分${seconds}秒`; if (typeof tippy !== 'undefined') tippy(`#${elementId}`, { content: formattedTime, placement: "right", appendTo: () => document.body, maxWidth: "none", interactive: true, offset: [0, 8], theme: 'light-border', popperOptions: { modifiers: [{ name: 'preventOverflow', options: { boundary: document.body } }, { name: 'flip', options: { fallbackPlacements: ['top', 'bottom', 'left'] } }] } }); } } }
      updateDateElementTooltip("creation-date"); updateDateElementTooltip("expiration-date"); updateDateElementTooltip("updated-date"); updateDateElementTooltip("available-date");
    });
  </script>
</body>
</html>
