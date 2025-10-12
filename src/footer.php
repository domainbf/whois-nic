<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>NIC.BN - 域名查询与出售</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: #f8fafc;
            color: #333;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 10px;
        }

        .container {
            flex: 1;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 10px;
        }

        .container h1 {
            font-size: 2.5rem;
            color: #2d3748;
            font-weight: 700;
            text-align: center;
        }

        .footer {
            background: transparent;
            width: 100%;
            text-align: center;
            position: relative;
        }

        /* 美化底部信息布局 */
        .footer-info {
            background: rgba(255,255,255,0.85);
            border-radius: 14px;
            margin: 0 auto 10px auto;
            max-width: 95vw;
            padding: 8px 12px;
            box-shadow: 0 2px 14px rgba(200,200,210,0.09);
            font-size: 1rem;
            color: #2d3748;
            text-align: center;
        }
        .footer-info.strong {
            font-weight: 600;
            letter-spacing: 0.02em;
        }
        .footer-logo {
            margin: 10px auto 6px auto;
            max-width: 100px;
            filter: drop-shadow(0 2px 14px rgba(44,36,82,0.08));
            display: block;
        }
        .footer-copyright {
            font-size: 0.85rem;
            color: #718096;
            margin-bottom: 10px;
            text-align: center;
            display: inline-block;
        }

        /* 缩小版徽章样式，保持美观 */
        .footer-badge-container {
            display: block;
            margin: 0 auto 12px auto;
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
        /* 邮箱模式：嵌套黄色圆角块 */
        .footer-badge-bg.mail .footer-badge-dot {
            background: #2c2452;
        }
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
        }
        .footer-badge-bg.mail .footer-badge-inner a {
            color: #2c2452;
            text-decoration: none;
            font-weight: 700;
        }
        @media (max-width: 700px) {
            .footer-info {
                font-size: 0.92rem;
                padding: 6px 4px;
            }
            .footer-badge-bg, .footer-badge-bg.mail .footer-badge-inner {
                font-size: 0.8rem;
                padding: 0.28rem 0.5rem;
            }
            .footer-badge-eye {
                margin-right: 3px;
                margin-left: -2px;
            }
            .footer-badge-eye svg {
                width: 8px;
                height: 5px;
            }
            .footer-badge-dot {
                width: 5px;
                height: 5px;
                margin-right: 3px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 可选主内容 -->
        <h3>数据源于官方注册机构，仅供参考。</h3>
    </div>

    <footer class="footer">
        <div class="footer-info strong">
            数据源于官方注册机构，仅供参考。
        </div>
        <div class="footer-info">
            我们提供查询平台，但不储存任何查询数据。
        </div>
        <img class="footer-logo" src="/images/logo.png" alt="NIC.BN logo">
        <div class="footer-copyright">
            &copy; 2025 NIC.BN. All rights reserved.
        </div>
        <!-- 缩小版徽章美化放底部居中 -->
        <span class="footer-badge-container">
            <span class="footer-badge-bg available" id="footer-badge-bg">
                <!-- 眼睛SVG -->
                <span class="footer-badge-eye">
                  <svg width="14" height="9" viewBox="0 0 32 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <ellipse cx="16" cy="11" rx="15" ry="9" fill="#392A5C" opacity="0.3"/>
                    <ellipse cx="16" cy="11" rx="7" ry="4" fill="#392A5C" opacity="0.55"/>
                    <circle cx="16" cy="11" r="4" fill="white" opacity="0.85"/>
                    <circle cx="16" cy="11" r="2" fill="#2c2452"/>
                  </svg>
                </span>
                <span class="footer-badge-dot"></span>
                <span class="footer-badge-text" id="footer-badge-text">Available for freelance</span>
            </span>
        </span>
    </footer>

    <script>
        // 公告轮播逻辑原样
        document.addEventListener('DOMContentLoaded', function() {
            const announcements = document.querySelectorAll('.announcement');
            let currentIndex = 0;

            announcements[0].classList.add('active');

            function showNextAnnouncement() {
                announcements[currentIndex].classList.remove('active');
                currentIndex = (currentIndex + 1) % announcements.length;
                announcements[currentIndex].classList.add('active');
            }

            setInterval(showNextAnnouncement, 6000);
        });

        // 徽章切换逻辑
        (function(){
            var bg = document.getElementById('footer-badge-bg');
            var text = document.getElementById('footer-badge-text');
            function setBadgeContent(isBottom) {
                if(isBottom){
                    bg.className = 'footer-badge-bg mail';
                    text.innerHTML =
                        '<span class="footer-badge-inner">' +
                        '<a href="mailto:domain@nic.bn">domain@nic.bn</a>' +
                        '</span>';
                }else{
                    bg.className = 'footer-badge-bg available';
                    text.textContent = "Available for freelance";
                }
            }
            function updateBadge(){
                var isBottom = (window.innerHeight + window.scrollY) >= (document.body.offsetHeight - 2);
                setBadgeContent(isBottom);
            }
            window.addEventListener('scroll', updateBadge);
            window.addEventListener('resize', updateBadge);
            document.addEventListener('DOMContentLoaded', updateBadge);
            updateBadge();
        })();
    </script>
</body>
</html>
