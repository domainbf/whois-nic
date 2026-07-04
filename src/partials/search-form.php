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
                <kbd class="nw-kbd nw-kbd-inline" id="slash-hint" aria-hidden="true">/</kbd>
                <input
                  autocapitalize="off"
                  autocomplete="domain"
                  autocorrect="off"
                  <?= $domain ? "" : "autofocus"; ?>
                  class="input search-input"
                  id="domain"
                  inputmode="url"
                  name="domain"
                  placeholder="<?= htmlspecialchars(t('search_placeholder'), ENT_QUOTES, 'UTF-8'); ?>"
                  required
                  type="text"
                  value="<?= htmlspecialchars($domain ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <button class="search-clear" id="domain-clear" type="button" aria-label="<?= htmlspecialchars(t('clear'), ENT_QUOTES, 'UTF-8'); ?>">
                    <svg viewBox="0 0 16 16" fill="currentColor">
                        <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708" />
                    </svg>
                </button>
                <button class="button search-button" type="submit" aria-label="<?= htmlspecialchars(t('search_button'), ENT_QUOTES, 'UTF-8'); ?>">
                    <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" id="search-icon">
                        <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0" />
                    </svg>
                    <span><?= htmlspecialchars(t('search_button'), ENT_QUOTES, 'UTF-8'); ?></span>
                </button>
            </div>
            <!-- 搜索联想下拉（由 autocomplete.js 动态渲染）-->
            <div
              class="nw-suggest"
              id="nw-suggest"
              role="listbox"
              aria-label="<?= htmlspecialchars(t('suggest_aria'), ENT_QUOTES, 'UTF-8'); ?>"
              hidden
              data-label-recent="<?= htmlspecialchars(t('suggest_recent'), ENT_QUOTES, 'UTF-8'); ?>"
              data-label-suggest="<?= htmlspecialchars(t('suggest_domains'), ENT_QUOTES, 'UTF-8'); ?>"
              data-label-registered="<?= htmlspecialchars(t('status_registered'), ENT_QUOTES, 'UTF-8'); ?>"
              data-label-available="<?= htmlspecialchars(t('status_available'), ENT_QUOTES, 'UTF-8'); ?>"
              data-label-checking="<?= htmlspecialchars(t('status_checking'), ENT_QUOTES, 'UTF-8'); ?>"
              data-label-unknown="<?= htmlspecialchars(t('status_unknown'), ENT_QUOTES, 'UTF-8'); ?>"></div>
        </div>
        <!-- 查询选项已按原版隐藏：默认同时使用 WHOIS + RDAP，并保留价格查询 -->
        <input type="hidden" name="prices" value="1">
      </form>
      <?php
        // 根据解析结果区分状态：无效 / 保留 / 禁止注册 / 未找到 / 已注册(隐藏) / 可注册
        $resultMessage = null;
        $resultState = '';
        if ($domain) {
            if ($error && !empty($invalidDomain)) {
                // 仅格式/后缀真正非法时才提示"无效域名"
                $resultMessage = t('msg_invalid');
                $resultState = 'invalid';
            } elseif ($error && !empty($dnsActive)) {
                // 查询接口失败，但 DNS 显示该域名已被注册/在用
                $resultMessage = t('msg_taken');
                $resultState = 'taken';
            } elseif ($error) {
                // 查询失败（注册局/网络问题），非域名无效——提示稍后重试
                $resultMessage = t('msg_error');
                $resultState = 'error';
            } elseif ($parser->reserved) {
                $resultMessage = t('msg_reserved');
                $resultState = 'reserved';
            } elseif ($parser->prohibited) {
                $resultMessage = t('msg_prohibited');
                $resultState = 'prohibited';
            } elseif ($parser->registered) {
                // 已注册：直接展示下方信息卡片，此处不再提示
                $resultMessage = null;
            } elseif (!empty($dnsActive)) {
                // WHOIS/RDAP 查不到详情，但 DNS 检测到该域名已被注册/在用
                $resultMessage = t('msg_taken');
                $resultState = 'taken';
            } elseif ($parser->unknown) {
                $resultMessage = t('msg_unknown');
                $resultState = 'unknown';
            } else {
                $resultMessage = t('msg_available');
                $resultState = 'available';
            }
        }
      ?>
      <?php if ($domain && $resultMessage):
        // 状态 → 图标（内联 SVG，随卡片状态色着色）
        $stateIcons = [
          'available'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>',
          'reserved'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
          'prohibited' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m4.9 4.9 14.2 14.2"/></svg>',
          'invalid'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>',
          'unknown'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>',
          'taken'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
          'error'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
        ];
        $stateIcon  = $stateIcons[$resultState] ?? $stateIcons['unknown'];
        $stateTitle = t('title_' . $resultState);
      ?>
  <div class="domain-info-box domain-info-box--<?= htmlspecialchars($resultState, ENT_QUOTES, 'UTF-8'); ?>">
    <span class="domain-info-icon" aria-hidden="true"><?= $stateIcon; ?></span>
    <a class="domain-info-name" href="http://<?= htmlspecialchars($domain, ENT_QUOTES, 'UTF-8'); ?>" rel="nofollow noopener noreferrer" target="_blank"><?= htmlspecialchars($domain, ENT_QUOTES, 'UTF-8'); ?></a>
    <p class="domain-info-title"><?= htmlspecialchars($stateTitle, ENT_QUOTES, 'UTF-8'); ?></p>
    <p class="domain-info-sub"><?= htmlspecialchars($resultMessage, ENT_QUOTES, 'UTF-8'); ?></p>
  </div>
<?php endif; ?>
      <!-- 搜索框下方快捷键提示行（复刻 next-whois）-->
      <div class="nw-hotkeys" aria-hidden="true">
        <span class="nw-hotkey-item"><?= htmlspecialchars(t('hotkey_query'), ENT_QUOTES, 'UTF-8'); ?> <kbd class="nw-kbd">/</kbd></span>
        <span class="nw-hotkey-item"><?= htmlspecialchars(t('hotkey_clear'), ENT_QUOTES, 'UTF-8'); ?> <kbd class="nw-kbd">Esc</kbd></span>
      </div>
      <?php if (!$domain): ?>
      <!-- 首页：历史查询列表（由 history.js 基于 localStorage 渲染，支持翻页）-->
      <div class="nw-history" id="search-history" hidden>
        <div class="nw-history-divider"><span id="search-history-label"><?= htmlspecialchars(t('history_title'), ENT_QUOTES, 'UTF-8'); ?></span></div>
        <ul class="nw-history-list" id="search-history-list"></ul>
        <div class="nw-history-pager" id="search-history-pager" hidden>
          <button class="nw-pager-btn" id="history-prev" type="button" aria-label="<?= htmlspecialchars(t('prev_page'), ENT_QUOTES, 'UTF-8'); ?>">
            <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M10.354 3.646a.5.5 0 0 1 0 .708L6.707 8l3.647 3.646a.5.5 0 0 1-.708.708l-4-4a.5.5 0 0 1 0-.708l4-4a.5.5 0 0 1 .708 0"/></svg>
          </button>
          <span class="nw-pager-info" id="history-info"></span>
          <button class="nw-pager-btn" id="history-next" type="button" aria-label="<?= htmlspecialchars(t('next_page'), ENT_QUOTES, 'UTF-8'); ?>">
            <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M5.646 3.646a.5.5 0 0 1 .708 0l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L9.293 8 5.646 4.354a.5.5 0 0 1 0-.708"/></svg>
          </button>
        </div>
        <button class="nw-history-clear" id="history-clear" type="button"><?= htmlspecialchars(t('history_clear'), ENT_QUOTES, 'UTF-8'); ?></button>
      </div>
      <?php endif; ?>
    </div>
  </header>
