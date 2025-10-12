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

        .announcement-container {
            text-align: center;
            margin-bottom: 15px;
            width: 100%;
            overflow: hidden;
        }

        .announcement-box {
            padding: 8px;
            min-height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            width: 100%;
        }

        .announcement {
            font-size: 0.9rem;
            line-height: 1.4;
            opacity: 0;
            position: absolute;
            width: 90%;
            text-align: center;
            transition: opacity 0.5s ease;
            color: #4a5568;
        }

        .announcement.active {
            opacity: 1;
        }

        .host-info {
            padding: 10px;
            font-size: 0.9rem;
            color: #718096;
            margin-bottom: 10px;
            text-align: center;
        }

        .host-info a img {
            vertical-align: middle;
            max-width: 80%;
            height: auto;
            max-height: 60px;
        }

        .copyright {
            padding: 8px;
            font-size: 0.8rem;
            color: #718096;
            display: inline-block;
        }

        /* 缩小版徽章样式 */
        .footer-badge-container {
            display: inline-block;
            vertical-align: middle;
            margin-left: 1.2em;
            margin-bottom: 0.8em;
        }
        .footer-badge-bg {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            font-weight: 700;
            box-shadow: 0 4px 16px rgba(44,36,82,0.15);
            font-size: 1rem;
            padding: 0.65rem 1.2rem;
            background: #2c2452;
            color: #fff;
            transition: all 0.3s;
            position: relative;
        }
        .footer-badge-eye {
            display: flex;
            align-items: center;
            margin-right: 9px;
            margin-left: -6px;
        }
        .footer-badge-eye svg {
            width: 18px;
            height: 12px;
        }
        .footer-badge-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 9px;
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
            padding: 0.3em 0.7em;
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
            .footer-badge-bg, .footer-badge-bg.mail .footer-badge-inner {
                font-size: 0.85rem;
                padding: 0.35rem 0.6rem;
            }
            .footer-badge-eye {
                margin-right: 4px;
                margin-left: -3px;
            }
            .footer-badge-eye svg {
                width: 11px;
                height: 7px;
            }
            .footer-badge-dot {
                width: 6px;
                height: 6px;
                margin-right: 4px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h3>数据源于官方注册机构，仅供参考。</h3>
    </div>

    <footer class="footer">
        <div class="announcement-container">
            <div class="announcement-box">
                <div class="announcement active">RDAP+WHOIS 双核驱动提供准确数据。</div>
                <div class="announcement">我们提供查询平台，但不储存任何查询数据。</div>
                <div class="announcement">在售的域名，可👇点击{下方}进入列表查看，</div>
                <div class="announcement">不记录·不储存·所有数据仅保留在您本地浏览器。</div>
            </div>
        </div>

        <div class="host-info">
            <a href="https://www.hello.sn/domain" rel="noopener" target="_blank">
                <img src="/images/logo.png" alt="Logo">
            </a>
        </div>

        <div class="copyright">
            &copy; 2025 NIC.BN. All rights reserved.
        </div>
        <!-- 缩小版徽章紧跟版权 -->
        <span class="footer-badge-container">
            <span class="footer-badge-bg available" id="footer-badge-bg">
                <!-- 眼睛SVG -->
                <span class="footer-badge-eye">
                  <svg width="18" height="12" viewBox="0 0 32 22" fill="none" xmlns="http://www.w3.org/2000/svg">
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
