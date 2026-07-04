<nav class="topbar">
  <div class="topbar-inner">
    <a class="brand" href="<?= BASE; ?>" aria-label="<?= htmlspecialchars(t('nav_home'), ENT_QUOTES, 'UTF-8'); ?>">
      <span class="brand-logo is-animated" aria-hidden="true">W</span>
      <span class="brand-name">WHOIS+RDAP</span>
      <span class="brand-badge"><?= htmlspecialchars(t('brand_badge'), ENT_QUOTES, 'UTF-8'); ?></span>
    </a>
    <span class="topbar-divider" aria-hidden="true"></span>
    <div class="topbar-actions">
      <!-- 语言切换器（替换原 GitHub 图标）-->
      <div class="lang-switch" id="lang-switch">
        <button class="icon-btn lang-toggle" id="lang-toggle" type="button" aria-haspopup="true" aria-expanded="false" aria-label="<?= htmlspecialchars(t('nav_lang'), ENT_QUOTES, 'UTF-8'); ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="10"/>
            <path d="M2 12h20"/>
            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
          </svg>
        </button>
        <div class="lang-menu" id="lang-menu" role="menu" aria-label="<?= htmlspecialchars(t('nav_lang'), ENT_QUOTES, 'UTF-8'); ?>" hidden>
          <?php foreach (i18n_supported() as $code => $name): ?>
            <button class="lang-option<?= $code === i18n_lang() ? ' is-active' : ''; ?>" type="button" role="menuitemradio" aria-checked="<?= $code === i18n_lang() ? 'true' : 'false'; ?>" data-lang="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8'); ?>">
              <span class="lang-option-name"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></span>
              <svg class="lang-option-check" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M13.485 1.929a.75.75 0 0 1 .06 1.058l-7 8a.75.75 0 0 1-1.116.018l-3-3.25a.75.75 0 1 1 1.1-1.018l2.432 2.635 6.466-7.39a.75.75 0 0 1 1.058-.06z"/></svg>
            </button>
          <?php endforeach; ?>
        </div>
      </div>
      <button class="icon-btn theme-toggle" id="theme-toggle" type="button" aria-label="<?= htmlspecialchars(t('nav_theme'), ENT_QUOTES, 'UTF-8'); ?>">
        <svg class="icon-sun" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
          <path d="M8 11a3 3 0 1 1 0-6 3 3 0 0 1 0 6m0 1a4 4 0 1 0 0-8 4 4 0 0 0 0 8M8 0a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 0m0 13a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 13m8-5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 .5.5M3 8a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2A.5.5 0 0 1 3 8m10.657-5.657a.5.5 0 0 1 0 .707l-1.414 1.415a.5.5 0 1 1-.707-.708l1.414-1.414a.5.5 0 0 1 .707 0m-9.193 9.193a.5.5 0 0 1 0 .707L3.05 13.657a.5.5 0 0 1-.707-.707l1.414-1.414a.5.5 0 0 1 .707 0m9.193 2.121a.5.5 0 0 1-.707 0l-1.414-1.414a.5.5 0 0 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .707M4.464 4.465a.5.5 0 0 1-.707 0L2.343 3.05a.5.5 0 1 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .708" />
        </svg>
        <svg class="icon-moon" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
          <path d="M6 .278a.77.77 0 0 1 .08.858 7.2 7.2 0 0 0-.878 3.46c0 4.021 3.278 7.277 7.318 7.277q.792-.001 1.533-.16a.79.79 0 0 1 .81.316.73.73 0 0 1-.031.893A8.35 8.35 0 0 1 8.344 16C3.734 16 0 12.286 0 7.71 0 4.266 2.114 1.312 5.124.06A.75.75 0 0 1 6 .278" />
        </svg>
      </button>
    </div>
  </div>
</nav>
