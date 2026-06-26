    <?php require_once __DIR__ . "/../lib/icons.php"; ?>
    <?php if ($parser->registered): ?>
      <section class="messages">
        <div>
          <div class="message message-positive">
            <div class="message-data">
              <h1 class="message-title">
                  <svg width="1em" height="1em" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" class="message-icon">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16" />
                    <path d="m10.97 4.97-.02.022-3.473 4.425-2.093-2.094a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05" />
                  </svg>
                  <a href="http://<?= htmlspecialchars($domain, ENT_QUOTES, 'UTF-8'); ?>" rel="nofollow noopener noreferrer" target="_blank"><?= htmlspecialchars($domain, ENT_QUOTES, 'UTF-8'); ?></a>
              </h1>
              <?php if ($parser->registrar): ?>
                <?php
                  // 注册商官网：优先使用 WHOIS/RDAP 提供的 URL，缺失时用全球注册商映射智能识别
                  require_once __DIR__ . "/../lib/registrar-map.php";
                  $registrarLink = $parser->registrarURL ?: registrar_website($parser->registrar);
                ?>
                <div class="message-label">
                  <span class="message-icon-leading">
                    <?= inline_icon('credit-card'); ?>
                  </span>
                  注册平台
                </div>
                <div>
                  <?php if ($registrarLink): ?>
                    <a href="<?= htmlspecialchars($registrarLink, ENT_QUOTES, 'UTF-8'); ?>" rel="nofollow noopener noreferrer" target="_blank"><?= htmlspecialchars($parser->registrar, ENT_QUOTES, 'UTF-8'); ?></a>
                  <?php else: ?>
                    <?= htmlspecialchars($parser->registrar, ENT_QUOTES, 'UTF-8'); ?>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
              <?php if ($parser->creationDate): ?>
                <div class="message-label">
                  <span class="message-icon-leading">
                    <?= inline_icon('calendar-days'); ?>
                  </span>
                  创建日期
                </div>
                <div>
                  <?php if ($parser->creationDateISO8601 === null): ?>
                    <span><?= htmlspecialchars($parser->creationDate, ENT_QUOTES, 'UTF-8'); ?></span>
                  <?php elseif (str_ends_with($parser->creationDateISO8601, "Z")): ?>
                    <span id="creation-date" data-iso8601="<?= htmlspecialchars($parser->creationDateISO8601, ENT_QUOTES, 'UTF-8'); ?>">
                      <?= htmlspecialchars($parser->creationDate, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                  <?php else: ?>
                    <span id="creation-date" data-iso8601="<?= htmlspecialchars($parser->creationDateISO8601, ENT_QUOTES, 'UTF-8'); ?>">
                      <?= htmlspecialchars($parser->creationDate, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
              <?php if ($parser->expirationDate): ?>
                <div class="message-label">
                  <span class="message-icon-leading">
                    <?= inline_icon('calendar-xmark'); ?>
                  </span>
                  到期日期
                </div>
                <div>
                  <?php if ($parser->expirationDateISO8601 === null): ?>
                    <span><?= htmlspecialchars($parser->expirationDate, ENT_QUOTES, 'UTF-8'); ?></span>
                  <?php elseif (str_ends_with($parser->expirationDateISO8601, "Z")): ?>
                    <span id="expiration-date" data-iso8601="<?= htmlspecialchars($parser->expirationDateISO8601, ENT_QUOTES, 'UTF-8'); ?>">
                      <?= htmlspecialchars($parser->expirationDate, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                  <?php else: ?>
                    <span id="expiration-date" data-iso8601="<?= htmlspecialchars($parser->expirationDateISO8601, ENT_QUOTES, 'UTF-8'); ?>">
                      <?= htmlspecialchars($parser->expirationDate, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
              <?php if ($parser->updatedDate): ?>
                <div class="message-label">
                  <span class="message-icon-leading">
                    <?= inline_icon('rotate'); ?>
                  </span>
                  更新日期
                </div>
                <div>
                  <?php if ($parser->updatedDateISO8601 === null): ?>
                    <span><?= htmlspecialchars($parser->updatedDate, ENT_QUOTES, 'UTF-8'); ?></span>
                  <?php elseif (str_ends_with($parser->updatedDateISO8601, "Z")): ?>
                    <span id="updated-date" data-iso8601="<?= htmlspecialchars($parser->updatedDateISO8601, ENT_QUOTES, 'UTF-8'); ?>">
                      <?= htmlspecialchars($parser->updatedDate, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                  <?php else: ?>
                    <span id="updated-date" data-iso8601="<?= htmlspecialchars($parser->updatedDateISO8601, ENT_QUOTES, 'UTF-8'); ?>">
                      <?= htmlspecialchars($parser->updatedDate, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
              <?php if ($parser->availableDate): ?>
                <div class="message-label">
                  <span class="message-icon-leading">
                    <?= inline_icon('mobile-screen'); ?>
                  </span>
                  可用日期
                </div>
                <div>
                  <?php if ($parser->availableDateISO8601 === null): ?>
                    <span><?= htmlspecialchars($parser->availableDate, ENT_QUOTES, 'UTF-8'); ?></span>
                  <?php elseif (str_ends_with($parser->availableDateISO8601, "Z")): ?>
                    <span id="available-date" data-iso8601="<?= htmlspecialchars($parser->availableDateISO8601, ENT_QUOTES, 'UTF-8'); ?>">
                      <?= htmlspecialchars($parser->availableDate, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                  <?php else: ?>
                    <span id="available-date" data-iso8601="<?= htmlspecialchars($parser->availableDateISO8601, ENT_QUOTES, 'UTF-8'); ?>">
                      <?= htmlspecialchars($parser->availableDate, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
              <?php if ($parser->status): ?>
                <div class="message-label">
  <span class="message-icon-leading">
    <?= inline_icon('circle-check'); ?>
  </span>
  域名状态
</div>
<div class="message-value-status">
  <?php
  // 全面的状态码到中文映射，从 lib/status-map.php 载入
  $statusMapping = require __DIR__ . "/../lib/status-map.php";

  foreach ($parser->status as $status): ?>
    <div>
      <?php if ($status["url"]): ?>
        <a href="<?= htmlspecialchars($status["url"], ENT_QUOTES, 'UTF-8'); ?>" rel="nofollow noopener noreferrer" target="_blank">
          <?= htmlspecialchars(isset($statusMapping[$status["text"]]) ? $statusMapping[$status["text"]] : $status["text"], ENT_QUOTES, 'UTF-8'); ?>
        </a>
      <?php else: ?>
        <?= htmlspecialchars(isset($statusMapping[$status["text"]]) ? $statusMapping[$status["text"]] : $status["text"], ENT_QUOTES, 'UTF-8'); ?>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
              <?php endif; ?>
              <?php if ($parser->nameServers): ?>
                <div class="message-label">
                  <span class="message-icon-leading">
                    <?= inline_icon('server'); ?>
                  </span>
                  NS服务器
                </div>
                <div class="message-value-name-servers">
                  <?php foreach ($parser->nameServers as $nameServer): ?>
                    <div>
                      <?= htmlspecialchars($nameServer, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
            <?php if ($fetchPrices): ?>
              <div class="message-price" id="message-price" data-domain="<?= htmlspecialchars($domain ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <div class="skeleton"></div>
              </div>
            <?php endif; ?>
            <?php if ($fetchBeiAn): ?>
              <div class="message-beian" id="message-beian" data-domain="<?= htmlspecialchars($domain ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <div class="skeleton"></div>
              </div>
            <?php endif; ?>
            <?php if ($parser->age || $parser->remaining || $parser->pendingDelete || $parser->gracePeriod || $parser->redemptionPeriod): ?>
              <?php if ($parser->age || $parser->remaining): ?>
              <div class="message-tags message-tags-info">
                <?php if ($parser->age): ?>
                  <button class="message-tag message-tag-gray" id="age" data-seconds="<?= $parser->ageSeconds; ?>">
                    <svg width="1em" height="1em" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                      <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z" />
                      <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0" />
                    </svg>
                    <span>已经注册：<?= htmlspecialchars($parser->age, ENT_QUOTES, 'UTF-8'); ?></span>
                  </button>
                <?php endif; ?>
                <?php if ($parser->remaining): ?>
                  <button class="message-tag message-tag-gray" id="remaining" data-seconds="<?= $parser->remainingSeconds; ?>">
                    <svg width="1em" height="1em" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                      <path d="M2 1.5a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-1v1a4.5 4.5 0 0 1-2.557 4.06c-.29.139-.443.377-.443.59v.7c0 .213.154.451.443.59A4.5 4.5 0 0 1 12.5 13v1h1a.5.5 0 0 1 0 1h-11a.5.5 0 1 1 0-1h1v-1a4.5 4.5 0 0 1 2.557-4.06c.29-.139.443-.377.443-.59v-.7c0-.213-.154-.451-.443-.59A4.5 4.5 0 0 1 3.5 3V2h-1a.5.5 0 0 1-.5-.5m2.5.5v1a3.5 3.5 0 0 0 1.989 3.158c.533.256 1.011.791 1.011 1.491v.702c0 .7-.478 1.235-1.011 1.491A3.5 3.5 0 0 0 4.5 13v1h7v-1a3.5 3.5 0 0 0-1.989-3.158C8.978 9.586 8.5 9.052 8.5 8.351v-.702c0-.7.478-1.235 1.011-1.491A3.5 3.5 0 0 0 11.5 3V2h-1a.5.5 0 0 1-.5-.5m5.393 5.962a.5.5 0 0 0-.5.5v7a.5.5 0 0 0 1 0v-7a.5.5 0 0 0-.5-.5" />
                    </svg>
                    <span>距离过期：<?= htmlspecialchars($parser->remaining, ENT_QUOTES, 'UTF-8'); ?></span>
                  </button>
                <?php endif; ?>
              </div>
              <?php endif; ?>
<?php
// 域名特征标签：基于二级域名标签（SLD），多维度识别长度与字符构成
$sld = '';
if (!empty($parser->domain)) {
    $sldParts = explode('.', $parser->domain);
    $sld = $sldParts[0] ?? '';
}
$sldLen = $sld === '' ? 0 : (function_exists('mb_strlen') ? mb_strlen($sld) : strlen($sld));
$isAllDigits = $sld !== '' && preg_match('/^[0-9]+$/', $sld);
$isAllLetters = $sld !== '' && preg_match('/^[a-zA-Z]+$/', $sld);
$hasHyphen = $sld !== '' && strpos($sld, '-') !== false;

// 先用输出缓冲收集所有特征标签，仅当确有标签时才渲染容器，避免出现空白分隔线
ob_start();
?>
<?php if ($parser->ageSeconds && $parser->ageSeconds < 60 * 24 * 60 * 60): ?>
  <span class="message-tag message-tag-green">新注册</span>
<?php endif; ?>
<?php if (($parser->remainingSeconds ?? -1) >= 0 && $parser->remainingSeconds < 30 * 24 * 60 * 60): ?>
  <span class="message-tag message-tag-yellow">即将过期</span>
<?php endif; ?>
<?php if (($parser->ageSeconds ?? 0) >= 10 * 365 * 24 * 60 * 60): ?>
  <span class="message-tag message-tag-red">古董域名</span>
<?php endif; ?>
<?php if (($parser->remainingSeconds ?? 0) >= 5 * 365 * 24 * 60 * 60): ?>
  <span class="message-tag message-tag-blue">长期持有</span>
<?php endif; ?>
<?php if ($sldLen === 1): ?>
  <span class="message-tag message-tag-blue">单字符</span>
<?php elseif ($sldLen === 2): ?>
  <span class="message-tag message-tag-blue">双字符</span>
<?php elseif ($sldLen === 3): ?>
  <span class="message-tag message-tag-indigo">三字符</span>
<?php elseif ($sldLen === 4): ?>
  <span class="message-tag message-tag-indigo">四字符</span>
<?php endif; ?>
<?php if ($isAllDigits): ?>
  <span class="message-tag message-tag-purple"><?= $sldLen; ?>位纯数字</span>
<?php elseif ($isAllLetters && $sldLen >= 2 && $sldLen <= 4): ?>
  <span class="message-tag message-tag-green">纯字母</span>
<?php endif; ?>
<?php if ($hasHyphen): ?>
  <span class="message-tag message-tag-gray">含连字符</span>
<?php endif; ?>
<?php if ($parser->pendingDelete): ?>
  <span class="message-tag message-tag-red">待删除</span>
<?php elseif ($parser->remainingSeconds < 0): ?>
  <span class="message-tag message-tag-red">已过期</span>
<?php endif; ?>
<?php if ($parser->gracePeriod): ?>
  <span class="message-tag message-tag-yellow">宽限期</span>
<?php elseif ($parser->redemptionPeriod): ?>
  <span class="message-tag message-tag-blue">赎回期</span>
<?php endif; ?>
<?php
$featureTagsHtml = ob_get_clean();
if (trim($featureTagsHtml) !== ''):
?>
              <div class="message-tags message-tags-feature"><?= $featureTagsHtml; ?></div>
<?php endif; ?>
<?php endif; ?>
          </div>
        </div>
      </section>
    <?php endif; ?>
