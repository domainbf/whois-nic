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
            <div class="search-and-button-container">
                <div class="search-box">
                    <input
                      autocapitalize="off"
                      autocomplete="domain"
                      autocorrect="off"
                      <?= $domain ? "" : "autofocus"; ?>
                      class="input search-input"
                      id="domain"
                      inputmode="url"
                      name="domain"
                      placeholder="示例：NIC.RW"
                      required
                      type="text"
                      value="<?= htmlspecialchars($domain ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    <button class="search-clear" id="domain-clear" type="button" aria-label="Clear">
                        <svg width="1em" height="1em" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708" />
                        </svg>
                    </button>
                </div>
                <button class="button search-button">
                    <svg width="1em" height="1em" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" id="search-icon">
                        <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0" />
                    </svg>
                    <span>查询</span>
                </button>
            </div>
            <div class="checkboxes">
                <div class="checkbox">
                  <input <?= in_array("whois", $dataSource, true) ? "checked" : "" ?> class="checkbox-trigger" id="checkbox-whois" name="whois" type="checkbox" value="1">
                  <label class="checkbox-label" for="checkbox-whois">
                    <span class="checkbox-leading-icon">
                      <svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true">
                        <circle cx="9" cy="9" r="9" fill="#222"/>
                        <text x="9" y="13" text-anchor="middle" fill="#fff" font-size="12" font-family="Arial" font-weight="bold">W</text>
                      </svg>
                    </span>
                    WHOIS
                  </label>
                  <div class="checkbox-icon-wrapper">
                    <svg class="checkbox-icon checkbox-icon-checkmark" width="50" height="39.69" viewBox="0 0 50 39.69" aria-hidden="true">
                      <path d="M43.68 0L16.74 27.051 6.319 16.63l-6.32 6.32 16.742 16.74L50 6.32z" />
                    </svg>
                  </div>
                </div>
                <div class="checkbox">
                  <input <?= in_array("rdap", $dataSource, true) ? "checked" : "" ?> class="checkbox-trigger" id="checkbox-rdap" name="rdap" type="checkbox" value="1">
                  <label class="checkbox-label" for="checkbox-rdap">
                    <span class="checkbox-leading-icon">
                      <svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true">
                        <circle cx="9" cy="9" r="9" fill="#222"/>
                        <text x="9" y="13" text-anchor="middle" fill="#fff" font-size="12" font-family="Arial" font-weight="bold">R</text>
                      </svg>
                    </span>
                    RDAP
                  </label>
                  <div class="checkbox-icon-wrapper">
                    <svg class="checkbox-icon checkbox-icon-checkmark" width="50" height="39.69" viewBox="0 0 50 39.69" aria-hidden="true">
                      <path d="M43.68 0L16.74 27.051 6.319 16.63l-6.32 6.32 16.742 16.74L50 6.32z" />
                    </svg>
                  </div>
                </div>
                <div class="checkbox">
                  <input <?= $fetchPrices ? "checked" : "" ?> class="checkbox-trigger" id="checkbox-prices" name="prices" type="checkbox" value="1">
                  <label class="checkbox-label" for="checkbox-prices">
                    <span class="checkbox-leading-icon">
                      <svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true">
                        <circle cx="9" cy="9" r="9" fill="#222"/>
                        <text x="9" y="13" text-anchor="middle" fill="#fff" font-size="12" font-family="Arial" font-weight="bold">$</text>
                      </svg>
                    </span>
                    价格
                  </label>
                  <div class="checkbox-icon-wrapper">
                    <svg class="checkbox-icon checkbox-icon-checkmark" width="50" height="39.69" viewBox="0 0 50 39.69" aria-hidden="true">
                      <path d="M43.68 0L16.74 27.051 6.319 16.63l-6.32 6.32 16.742 16.74L50 6.32z" />
                    </svg>
                  </div>
                </div>
            </div>
        </form>
    </div>
</header>
