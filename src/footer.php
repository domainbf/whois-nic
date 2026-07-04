<?php
    // 确保多语言可用（footer 也被 login.php 单独引用，此时 i18n 可能尚未初始化）
    require_once __DIR__ . "/lib/i18n.php";
    if (!isset($GLOBALS["__LANG__"])) {
        i18n_init();
    }
?>
<style>
        .footer {
            width: 100%;
            position: relative;
            text-align: center;
            margin-top: auto;
            padding-top: 40px;
        }
        .footer-bottomarea {
            width: 100%;
            text-align: center;
            position: relative;
            padding-bottom: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 9px;
        }
        
        .footer-announcement-container {
            width: 100%;
            max-width: 96vw;
            box-sizing: border-box;
            position: relative;
            min-height: 65px; 
        }
        .footer-announcement-box {
            min-height: 65px;
            position: relative;
            width: 100%;
        }

        .footer-announcement {
            background: transparent;
            box-shadow: none;
            font-size: 0.95rem;
            color: hsl(var(--foreground));
            font-weight: 600;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.4em;
            padding: 6px 0; 
            margin: 0 auto 1px auto;
            max-width: 100%;
            opacity: 0;
            position: absolute;
            left: 0;
            right: 0;
            transition: opacity 0.5s, transform 0.3s;
            z-index: 1;
            white-space: normal;
            overflow: hidden;
            text-overflow: ellipsis;
            text-align: center;
            height: 65px;
            line-height: 1.4;
        }

        .footer-announcement.active {
            opacity: 1;
            position: absolute;
            z-index: 2;
            transform: translateY(0);
        }
        .footer-announcement .speaker {
            display: inline-flex;
            align-items: center;
            margin-right: 0.28em;
            animation: speaker-bounce 1.2s infinite;
        }
        @keyframes speaker-bounce {
            0%,100% { transform: scale(1) rotate(-8deg);}
            50% { transform: scale(1.08) rotate(8deg);}
        }
        
        .footer-logo-link {
            display: inline-block;
            margin: 5px auto;
            max-width: 180px;
            transition: all 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }
        .footer-logo-link:hover {
            transform: translateY(-2px);
            filter: drop-shadow(0 4px 16px rgba(44,36,82,0.15));
        }
        .footer-logo {
            max-width: 100%;
            filter: drop-shadow(0 2px 14px rgba(44,36,82,0.12));
            display: block;
        }
        
        .footer-badge-container {
            display: block;
            margin: 0 auto 0 auto;
            text-align: center;
        }
        .footer-badge-bg {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            font-weight: 700;
            box-shadow: 0 4px 16px hsl(var(--foreground) / 0.13);
            font-size: 0.98rem;
            padding: 0.42rem 0.88rem;
            background: #2c2452;
            color: #fff;
            border: 1px solid hsl(var(--border));
            transition: all 0.3s;
            position: relative;
            cursor: pointer;
            user-select: none;
        }
        .footer-badge-icon {
            display: flex;
            align-items: center;
            margin-right: 0.4em; 
            margin-left: -0.1em; 
        }
        .footer-badge-icon svg {
            width: 1.1em; 
            height: 1.1em;
        }
        .footer-badge-dot {
            display: none; 
        }
        .footer-badge-bg.mail .footer-badge-dot { background: #2c2452; }
        .footer-badge-bg.mail .footer-badge-inner {
            background: #c7ff35;
            color: #2c2452;
            border-radius: 999px;
            padding: 0.18em 0.42em;
            margin-left: 0.08em;
            font-weight: 700;
            font-size: 0.98rem;
            box-shadow: 0 1px 4px rgba(44,36,82,0.08);
            display: inline-block;
            transition: background 0.3s, color 0.3s;
        }
        .footer-badge-bg.mail .footer-badge-inner a {
            color: #2c2452;
            text-decoration: none;
            font-weight: 700;
        }
        
        .footer-copyright {
            font-size: 0.82rem;
            color: hsl(var(--muted-foreground));
            width: 100%;
            text-align: center;
            padding: 10px 0;
            background: transparent;
        }

        /* 鸣谢区：版权下方的数据来源与作者致谢 */
        .footer-credits {
            width: 100%;
            max-width: 96vw;
            margin: 0 auto;
            padding: 12px 16px 4px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
        }
        .footer-credits-divider {
            width: 48px;
            height: 2px;
            border-radius: 2px;
            background: hsl(var(--border));
            margin-bottom: 4px;
        }
        .footer-credits-line {
            font-size: 0.8rem;
            line-height: 1.5;
            color: hsl(var(--muted-foreground));
            text-align: center;
        }
        .footer-credits-line .credit-label {
            color: hsl(var(--muted-foreground) / 0.75);
            margin-right: 0.2em;
        }
        .footer-credits-line a {
            color: hsl(var(--foreground));
            text-decoration: none;
            font-weight: 600;
            border-bottom: 1px dashed hsl(var(--border-hover, var(--border)));
            transition: color 0.2s, border-color 0.2s;
        }
        .footer-credits-line a:hover {
            color: hsl(var(--primary));
            border-color: hsl(var(--primary));
        }
        .footer-credits-sep {
            color: hsl(var(--border));
            margin: 0 0.45em;
        }
        @media (max-width: 700px) {
            .footer-credits { max-width: 100vw; padding: 10px 12px 4px; }
            .footer-credits-line { font-size: 0.72rem; }
        }

        @media (max-width: 700px) {
            .footer-announcement { 
                font-size: 0.72rem; 
                padding: 6px 12px; 
                height: 65px;
                white-space: normal;
                word-break: break-word;
            }
            .footer-announcement .speaker {
                margin-right: 0.18em;
            }
            .footer-announcement-container {
                min-height: 65px; 
                max-width: 100vw;
            }
            .footer-logo-link {
                max-width: 110px;
                margin: 4px auto;
            }
            .footer-bottomarea { gap: 5px; padding-bottom: 10px; }
            .footer-badge-bg, .footer-badge-bg.mail .footer-badge-inner {
                font-size: 0.7rem;
                padding: 0.18rem 0.33rem;
            }
            .footer-badge-icon { 
                margin-right: 0.3em; 
                margin-left: -0.1em; 
            }
            .footer-badge-icon svg { 
                width: 1.1em; 
                height: 1.1em;
            }
        }

        @media (max-width: 380px) {
            .footer-announcement {
                font-size: 0.66rem;
                padding: 6px 4px;
            }
        }
    </style>
    <footer class="footer">
        <div class="footer-bottomarea">
            <div class="footer-announcement-container">
                <div class="footer-announcement-box">
                    <?php
                        $footerAnnouncements = [
                            t('footer_ann1'),
                            t('footer_ann2'),
                            t('footer_ann3'),
                            t('footer_ann4'),
                        ];
                        foreach ($footerAnnouncements as $idx => $ann):
                    ?>
                    <div class="footer-announcement<?= $idx === 0 ? ' active' : ''; ?>">
                        <span class="speaker">
                            <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                                <g><path d="M3 7v4h3l4 4V3L6 7H3z" fill="#2c2452"></path><path d="M14.5 9a4.5 4.5 0 0 0-2.5-4v8a4.5 4.5 0 0 0 2.5-4z" fill="#c7ff35"></path></g>
                            </svg>
                        </span>
                        <?= htmlspecialchars($ann, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <a href="https://hello.sn/domain" target="_blank" class="footer-logo-link">
                <img class="footer-logo" src="/images/logo.png" alt="NIC.BN logo">
            </a>
            <span class="footer-badge-container">
                <span class="footer-badge-bg available" id="footer-badge-bg" onclick="toggleBadge()">
                    <span class="footer-badge-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" fill="none">
                            <circle cx="16" cy="16" r="14" stroke="#c7ff35" stroke-width="2" fill="#2c2452"/>
                            <rect x="10" y="13" width="12" height="6" rx="2" fill="#c7ff35"/>
                            <circle cx="16" cy="16" r="2" fill="#2c2452"/>
                        </svg>
                    </span>
                    <span class="footer-badge-dot"></span>
                    <span class="footer-badge-text" id="footer-badge-text"><?= htmlspecialchars(t('footer_badge'), ENT_QUOTES, 'UTF-8'); ?></span>
                </span>
            </span>
        </div>
        <div class="footer-copyright">
            &copy; 2026 不讲·李. <?= htmlspecialchars(t('footer_rights'), ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <div class="footer-credits">
            <div class="footer-credits-divider"></div>
            <div class="footer-credits-line">
                <span class="credit-label"><?= htmlspecialchars(t('footer_price_by'), ENT_QUOTES, 'UTF-8'); ?></span>
                <a href="https://www.miqingju.com" target="_blank" rel="noopener noreferrer">米情局</a>
                <span class="footer-credits-sep">·</span>
                <a href="https://www.nazhumi.com" target="_blank" rel="noopener noreferrer">哪煮米</a>
                <span class="credit-label"><?= htmlspecialchars(t('footer_provided'), ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="footer-credits-line">
                <span class="credit-label"><?= htmlspecialchars(t('footer_thanks'), ENT_QUOTES, 'UTF-8'); ?></span>
                <a href="https://github.com/reg233/whois-domain-lookup" target="_blank" rel="noopener noreferrer">reg233</a>
                <span class="footer-credits-sep">·</span>
                <span class="credit-label"><?= htmlspecialchars(t('footer_opensource'), ENT_QUOTES, 'UTF-8'); ?></span>
                <a href="https://github.com/reg233/whois-domain-lookup" target="_blank" rel="noopener noreferrer">whois-domain-lookup</a>
            </div>
        </div>
    </footer>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const announcements = document.querySelectorAll('.footer-announcement');
            let currentIndex = 0;
            function showNextAnnouncement() {
                if (announcements.length > 0) {
                    announcements[currentIndex].classList.remove('active');
                    currentIndex = (currentIndex + 1) % announcements.length;
                    announcements[currentIndex].classList.add('active');
                }
            }
            if (announcements.length > 0) {
                announcements[0].classList.add('active');
                setInterval(showNextAnnouncement, 6000);
            }
        });

        let badgeMail = false;
        function toggleBadge() {
            badgeMail = !badgeMail;
            const bg = document.getElementById('footer-badge-bg');
            const text = document.getElementById('footer-badge-text');
            if(badgeMail){
                bg.className = 'footer-badge-bg mail';
                text.innerHTML =
                    '<span class="footer-badge-inner">' +
                    '<a href="mailto:domain@nic.rw">非必要请勿联系</a>' +
                    '</span>';
            }else{
                bg.className = 'footer-badge-bg available';
                text.textContent = <?= json_encode(t('footer_badge'), JSON_UNESCAPED_UNICODE); ?>;
            }
        }
    </script>
