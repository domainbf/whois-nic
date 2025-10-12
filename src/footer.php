<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>NIC.BN - 域名查询与出售</title>
    <style>
        body {
            background: #f8fafc;
            color: #333;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .footer {
            width: 100%;
            position: relative;
            text-align: center;
        }
        .footer-bottomarea {
            width: 100%;
            text-align: center;
            position: relative;
            margin-bottom: 38px; /* 给版权腾出空间 */
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 9px; /* 紧凑间距 */
        }
        /* 公告样式 */
        .footer-announcement-container {
            width: 100%;
            max-width: 96vw;
            box-sizing: border-box;
            position: relative;
            min-height: 30px;
        }
        .footer-announcement-box {
            min-height: 30px;
            position: relative;
            width: 100%;
        }
        .footer-announcement {
            background: rgba(255,255,255,0.93);
            border-radius: 13px;
            box-shadow: 0 2px 14px rgba(200,200,210,0.09);
            font-size: 0.95rem;
            color: #25304a;
            font-weight: 600;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 0.4em;
            padding: 6px 13px 6px 10px;
            margin: 0 auto 1px auto;
            max-width: 100%;
            opacity: 0;
            position: absolute;
            left: 0; right: 0;
            transition: opacity 0.5s;
            z-index: 2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .footer-announcement.active {
            opacity: 1;
            position: static;
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
        /* LOGO美化且居中 */
        .footer-logo {
            margin: 5px auto 5px auto;
            max-width: 130px;
            filter: drop-shadow(0 2px 14px rgba(44,36,82,0.12));
            display: block;
            transition: max-width 0.3s, max-height 0.3s;
        }
        @media (max-width: 700px) {
            .footer-announcement { font-size: 0.77rem; padding: 5px 5px 5px 4px; }
            .footer-logo { max-width: 88px; margin: 4px auto 4px auto; }
            .footer-bottomarea { gap: 5px; margin-bottom: 34px;}
        }
        /* 合作徽章美化，域名SVG图标 */
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
            box-shadow: 0 4px 16px rgba(44,36,82,0.13);
            font-size: 0.98rem;
            padding: 0.42rem 0.88rem;
            background: #2c2452;
            color: #fff;
            transition: all 0.3s;
            position: relative;
            cursor: pointer;
            user-select: none;
        }
        .footer-badge-icon {
            display: flex;
            align-items: center;
            margin-right: 7px;
            margin-left: -4px;
        }
        .footer-badge-icon svg {
            width: 15px;
            height: 15px;
        }
        .footer-badge-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 7px;
            transition: all 0.3s;
            background: #c0ff2e;
        }
        .footer-badge-text {
            font-weight: 700;
            font-size: 0.98rem;
            transition: all 0.3s;
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
        @media (max-width: 700px) {
            .footer-badge-bg, .footer-badge-bg.mail .footer-badge-inner {
                font-size: 0.7rem;
                padding: 0.18rem 0.33rem;
            }
            .footer-badge-icon { margin-right: 2px; margin-left: -2px; }
            .footer-badge-icon svg { width: 8px; height: 8px; }
            .footer-badge-dot { width: 4px; height: 4px; margin-right: 2px; }
        }
        /* 版权固定底部，透明无背景框 */
        .footer-copyright {
            font-size: 0.82rem;
            color: #718096;
            width: 100vw;
            text-align: center;
            display: block;
            position: fixed;
            left: 0;
            bottom: 0;
            background: transparent;
            z-index: 111;
            padding: 4px 0 4px 0;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 主内容 -->
    </div>
    <footer class="footer">
        <!-- 底部区：公告/logo/徽章整体在版权上方紧凑排列 -->
        <div class="footer-bottomarea">
            <div class="footer-announcement-container">
                <div class="footer-announcement-box">
                    <div class="footer-announcement active">
                        <span class="speaker">
                            <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                                <g>
                                    <path d="M3 7v4h3l4 4V3L6 7H3z" fill="#2c2452"></path>
                                    <path d="M14.5 9a4.5 4.5 0 0 0-2.5-4v8a4.5 4.5 0 0 0 2.5-4z" fill="#c7ff35"></path>
                                </g>
                            </svg>
                        </span>
                        RDAP+WHOIS 双核驱动提供准确数据。
                    </div>
                    <div class="footer-announcement">
                        <span class="speaker">
                            <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                                <g>
                                    <path d="M3 7v4h3l4 4V3L6 7H3z" fill="#2c2452"></path>
                                    <path d="M14.5 9a4.5 4.5 0 0 0-2.5-4v8a4.5 4.5 0 0 0 2.5-4z" fill="#c7ff35"></path>
                                </g>
                            </svg>
                        </span>
                        我们提供查询平台，但不储存任何查询数据。
                    </div>
                    <div class="footer-announcement">
                        <span class="speaker">
                            <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                                <g>
                                    <path d="M3 7v4h3l4 4V3L6 7H3z" fill="#2c2452"></path>
                                    <path d="M14.5 9a4.5 4.5 0 0 0-2.5-4v8a4.5 4.5 0 0 0 2.5-4z" fill="#c7ff35"></path>
                                </g>
                            </svg>
                        </span>
                        在售的域名，可👇点击{下方}进入列表查看，
                    </div>
                    <div class="footer-announcement">
                        <span class="speaker">
                            <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                                <g>
                                    <path d="M3 7v4h3l4 4V3L6 7H3z" fill="#2c2452"></path>
                                    <path d="M14.5 9a4.5 4.5 0 0 0-2.5-4v8a4.5 4.5 0 0 0 2.5-4z" fill="#c7ff35"></path>
                                </g>
                            </svg>
                        </span>
                        不记录·不储存·所有数据仅保留在您本地浏览器。
                    </div>
                </div>
            </div>
            <img class="footer-logo" src="/images/logo.png" alt="NIC.BN logo">
            <span class="footer-badge-container">
                <span class="footer-badge-bg available" id="footer-badge-bg" onclick="toggleBadge()">
                    <span class="footer-badge-icon">
                        <!-- 域名SVG图标（举例为全球/域名风格） -->
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" fill="none">
                            <circle cx="16" cy="16" r="14" stroke="#c7ff35" stroke-width="2" fill="#2c2452"/>
                            <rect x="10" y="13" width="12" height="6" rx="2" fill="#c7ff35"/>
                            <circle cx="16" cy="16" r="2" fill="#2c2452"/>
                        </svg>
                    </span>
                    <span class="footer-badge-dot"></span>
                    <span class="footer-badge-text" id="footer-badge-text">域名寻求合作</span>
                </span>
            </span>
        </div>
        <!-- 版权固定底部，透明无背景框 -->
        <div class="footer-copyright">
            &copy; 2025 NIC.BN. All rights reserved.
        </div>
    </footer>
    <script>
        // 公告轮播逻辑
        document.addEventListener('DOMContentLoaded', function() {
            const announcements = document.querySelectorAll('.footer-announcement');
            let currentIndex = 0;
            function showNextAnnouncement() {
                announcements[currentIndex].classList.remove('active');
                currentIndex = (currentIndex + 1) % announcements.length;
                announcements[currentIndex].classList.add('active');
            }
            announcements[0].classList.add('active');
            setInterval(showNextAnnouncement, 6000);
        });

        // 合作徽章点击显示邮箱
        let badgeMail = false;
        function toggleBadge() {
            badgeMail = !badgeMail;
            var bg = document.getElementById('footer-badge-bg');
            var text = document.getElementById('footer-badge-text');
            if(badgeMail){
                bg.className = 'footer-badge-bg mail';
                text.innerHTML =
                    '<span class="footer-badge-inner">' +
                    '<a href="mailto:domain@nic.bn">domain@nic.bn</a>' +
                    '</span>';
            }else{
                bg.className = 'footer-badge-bg available';
                text.textContent = "域名寻求合作";
            }
        }
    </script>
</body>
</html>
