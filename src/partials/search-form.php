  <header>
    <div>
      <form action="<?= BASE; ?>" id="form" method="get">
        <div class="search-and-button-container">
            <div class="search-box">
                <span class="search-leading" aria-hidden="true">
                    <svg viewBox="0 0 16 16" fill="currentColor">
                        <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0" />
                    </svg>
                </span>
                <input
                  autocapitalize="off"
                  autocomplete="domain"
                  autocorrect="off"
                  <?= $domain ? "" : "autofocus"; ?>
                  class="input search-input"
                  id="domain"
                  inputmode="url"
                  name="domain"
                  placeholder="输入域名进行查询，例如 NIC.RW"
                  required
                  type="text"
                  value="<?= htmlspecialchars($domain ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <button class="search-clear" id="domain-clear" type="button" aria-label="清除">
                    <svg viewBox="0 0 16 16" fill="currentColor">
                        <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708" />
                    </svg>
                </button>
                <button class="button search-button" type="submit" aria-label="查询">
                    <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" id="search-icon">
                        <path d="M15.964.686a.5.5 0 0 0-.65-.65L.767 5.855H.766l-.452.18a.5.5 0 0 0-.082.887l.41.26.001.002 4.995 3.178 3.178 4.995.002.002.26.41a.5.5 0 0 0 .886-.083zm-1.833 1.89L6.637 10.07l-.215-.338a.5.5 0 0 0-.154-.154l-.338-.215 7.494-7.494 1.178-.471z" />
                    </svg>
                    <span>查询</span>
                </button>
            </div>
        </div>
        <!-- 查询选项已按原版隐藏：默认同时使用 WHOIS + RDAP，并保留价格查询 -->
        <input type="hidden" name="prices" value="1">
      </form>
      <?php
        // 根据解析结果区分状态：无效 / 保留 / 禁止注册 / 未找到 / 已注册(隐藏) / 可注册
        $resultMessage = null;
        $resultState = '';
        if ($domain) {
            if ($error) {
                $resultMessage = "❌ 这是一个无效的域名，请检查格式后重试。";
                $resultState = 'invalid';
            } elseif ($parser->reserved) {
                $resultMessage = "🔒 该域名已被注册局保留，暂不开放注册。";
                $resultState = 'reserved';
            } elseif ($parser->prohibited) {
                $resultMessage = "🚫 该域名被注册局禁止或限制注册。";
                $resultState = 'prohibited';
            } elseif ($parser->unknown) {
                $resultMessage = "🔍 未找到该域名的注册信息。";
                $resultState = 'unknown';
            } elseif ($parser->registered) {
                // 已注册：直接展示下方信息卡片，此处不再提示
                $resultMessage = null;
            } else {
                $resultMessage = "✅ 恭喜！该域名尚未注册，可以立即注册。";
                $resultState = 'available';
            }
        }
      ?>
      <?php if ($domain && $resultMessage): ?>
  <div class="domain-info-box domain-info-box--<?= htmlspecialchars($resultState, ENT_QUOTES, 'UTF-8'); ?>">
    <a href="http://<?= htmlspecialchars($domain, ENT_QUOTES, 'UTF-8'); ?>" rel="nofollow noopener noreferrer" target="_blank">
      <p style="margin-bottom: 5px; font-size: 1.2em; word-break: break-all; overflow-wrap: break-word;"><?= htmlspecialchars($domain, ENT_QUOTES, 'UTF-8'); ?></p>
    </a>
    <p><?= $resultMessage; ?></p>
  </div>
<?php endif; ?>
      <?php if (!$domain): ?>
      <!-- 首页大号渐变动画标题 -->
      <div class="hero">
        <h1 class="hero-title">WHOIS</h1>
        <p class="hero-subtitle">专业的 WHOIS / RDAP 域名查询工具</p>
        <div class="hero-links">
          <button type="button" class="hero-chip" data-domain="nic.rw">nic.rw</button>
          <button type="button" class="hero-chip" data-domain="google.com">google.com</button>
          <button type="button" class="hero-chip" data-domain="github.com">github.com</button>
          <button type="button" class="hero-chip" data-domain="vercel.com">vercel.com</button>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </header>
