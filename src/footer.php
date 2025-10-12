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
            padding: 10px;
        }
        .footer {
            background: transparent;
            width: 100%;
            text-align: center;
            position: relative;
            margin-top: 30px;
        }
        /* 公告样式, 保证移动端一行显示 */
        .footer-announcement-container {
            margin: 0 auto 10px auto;
            max-width: 96vw;
            box-sizing: border-box;
            position: relative;
            min-height: 32px;
        }
        .footer-announcement-box {
            min-height: 32px;
            position: relative;
        }
        .footer-announcement {
            background: rgba(255,255,255,0.93);
            border-radius: 13px;
            box-shadow: 0 2px 14px rgba(200,200,210,0.09);
            font-size: 1.03rem;
            color: #25304a;
            font-weight: 600;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 0.45em;
            padding: 6px 12px 6px 8px;
            margin: 0 auto;
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
            margin-right: 0.32em;
            animation: speaker-bounce 1.2s infinite;
        }
        @keyframes speaker-bounce {
            0%,100% { transform: scale(1) rotate(-8deg);}
            50% { transform: scale(1.08) rotate(8deg);}
        }
        /* LOGO放大且居中 */
        .footer-logo {
            margin: 14px auto 10px auto;
            max-width: 165px;
            filter: drop-shadow(0 2px 14px rgba(44,36,82,0.12));
            display: block;
            transition: max-width 0.3s, max-height 0.3s;
        }
        @media (max-width: 700px) {
            .footer-announcement { font-size: 0.81rem; padding: 5px 6px 5px 4px; }
            .footer-logo { max-width: 125px; margin: 10px auto 7px auto; }
        }
        /* 版权固定在底部 */
        .footer-copyright {
            font-size: 0.85rem;
            color: #718096;
            width: 100vw;
            text-align: center;
            display: block;
            position: fixed;
            left: 0;
            bottom: 0;
            background: rgba(255,255,255,0.93);
            z-index: 111;
            padding: 5px 0 5px 0;
        }
        /* 合作徽章美化，点击显示 */
        .footer-badge-container {
            display: block;
            margin: 0 auto 14px auto;
            text-align: center;
        }
        .footer-badge-bg {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            font-weight: 700;
            box-shadow: 0 4px 16px rgba(44,36,82,0.15);
            font-size: 1rem;
            padding: 0.52rem 1.1rem;
            background: #2c2452;
            color: #fff;
            transition: all 0.3s;
            position: relative;
            cursor: pointer;
            user-select: none;
        }
        .footer-badge-eye {
            display: flex;
            align-items: center;
            margin-right: 7px;
            margin-left: -4px;
        }
        .footer-badge-eye svg {
            width: 14px;
            height: 9px;
        }
        .footer-badge-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 7px;
            transition: all 0.3s;
            background: #c0ff2e;
        }
        .footer-badge-text {
            font-weight: 700;
            font-size: 1rem;
            transition: all 0.3s;
        }
        .footer-badge-bg.mail .footer-badge-dot { background: #2c2452; }
        .footer-badge-bg.mail .footer-badge-inner {
            background: #c7ff35;
            color: #2c2452;
            border-radius: 999px;
            padding: 0.2em 0.5em;
            margin-left: 0.1em;
            font-weight: 700;
            font-size: 1rem;
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
                font-size: 0.82rem;
                padding: 0.28rem 0.5rem;
            }
            .footer-badge-eye { margin-right: 3px; margin-left: -2px; }
            .footer-badge-eye svg { width: 8px; height: 5px; }
            .footer-badge-dot { width: 5px; height: 5px; margin-right: 3px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 主内容 -->
    </div>
    <footer class="footer">
        <!-- 公告区（logo上方，4条轮播） -->
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
        <!-- 合作徽章点击显示 -->
        <span class="footer-badge-container">
            <span class="footer-badge-bg available" id="footer-badge-bg" onclick="toggleBadge()">
                <span class="footer-badge-eye">
                  <svg width="14" height="9" viewBox="0 0 32 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <ellipse cx="16" cy="11" rx="15" ry="9" fill="#392A5C" opacity="0.3"/>
                    <ellipse cx="16" cy="11" rx="7" ry="4" fill="#392A5C" opacity="0.55"/>
                    <circle cx="16" cy="11" r="4" fill="white" opacity="0.85"/>
                    <circle cx="16" cy="11" r="2" fill="#2c2452"/>
                  </svg>
                </span>
                <span class="footer-badge-dot"></span>
                <span class="footer-badge-text" id="footer-badge-text">域名寻求合作</span>
            </span>
        </span>
        <!-- 版权固定底部 -->
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
