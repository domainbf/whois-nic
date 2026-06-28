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
                        <path d="M15.964.686a.5.5 0 0 0-.65-.65L.767 5.855H.766l-.452.18a.5.5 0 0 0-.082.887l.41.26.001.002 4.995 3.178 3.178 4.995.002.002.26.41a.5.5 0 0 0 .886-.083zm-1.833 1.89L6.637 10.07l-.215-.338a.5.5 0 0 0-.154-.154l-.338-.215 7.494-7.494 1.178-.471z" />
                    </svg>
                    <span><?= htmlspecialchars(t('search_button'), ENT_QUOTES, 'UTF-8'); ?></span>
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
                $resultMessage = t('msg_invalid');
                $resultState = 'invalid';
            } elseif ($parser->reserved) {
                $resultMessage = t('msg_reserved');
                $resultState = 'reserved';
            } elseif ($parser->prohibited) {
                $resultMessage = t('msg_prohibited');
                $resultState = 'prohibited';
            } elseif ($parser->unknown) {
                $resultMessage = t('msg_unknown');
                $resultState = 'unknown';
            } elseif ($parser->registered) {
                // 已注册：直接展示下方信息卡片，此处不再提示
                $resultMessage = null;
            } else {
                $resultMessage = t('msg_available');
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
