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

        /* 徽章样式完全还原示例图片 */
        .footer-badge-container {
            display: inline-block;
            vertical-align: middle;
            margin-left: 1.2em;
            transition: opacity 0.3s;
        }
        .footer-badge {
            display: flex;
            align-items: center;
            padding: 1.4rem 2.2rem;
            border-radius: 2.2rem;
            font-weight: 600;
            box-shadow: 0 8px 32px rgba(44,36,82,0.15);
            background: #2c2452;
            color: #fff;
            transition: all 0.3s;
            font-size: 2rem;
            position: relative;
        }
        .footer-badge-eye {
            display: flex;
            align-items: center;
            margin-right: 8px;
            margin-left: -6px;
        }
        .footer-badge-dot {
            width: 15px;
            height: 15px;
            border-radius: 50%;
            display: inline-block;
            background: #c0ff2e;
            margin-left: 8px;
            margin-right: 12px;
            transition: all 0.3s;
        }
        .footer-badge a {
            font-weight: 600;
            color: #2c2452;
            text-decoration: none;
        }
        @media (max-width: 600px) {
            .footer-badge {
                padding: 0.7rem 1.1rem;
                font-size: 1rem;
            }
            .footer-badge-eye svg {
                width: 20px;
                height: 11px;
            }
            .footer-badge-dot {
                width: 8px;
                height: 8px;
                margin-left: 4px;
                margin-right: 6px;
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
        <!-- 底部徽章，紧跟在版权后方 -->
        <span class="footer-badge-container" id="footer-badge-container">
          <span class="footer-badge" id="footer-badge">
            <!-- 眼睛 SVG图标 -->
            <span class="footer-badge-eye">
              <svg width="40" height="24" viewBox="0 0 40 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <ellipse cx="20" cy="12" rx="16" ry="8" fill="#392A5C" opacity="0.35"/>
                <ellipse cx="20" cy="12" rx="8" ry="4.5" fill="#392A5C" opacity="0.45"/>
                <circle cx="20" cy="12" r="4" fill="white" opacity="0.9"/>
                <circle cx="20" cy="12" r="2" fill="#2c2452"/>
              </svg>
            </span>
            <!-- 右侧圆点 -->
            <span class="footer-badge-dot" id="footer-badge-dot"></span>
            <!-- 文字内容（邮箱可跳转） -->
            <span id="footer-badge-text"></span>
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

        // 底部徽章逻辑
        (function(){
          var badge = document.getElementById('footer-badge');
          var dot = document.getElementById('footer-badge-dot');
          var text = document.getElementById('footer-badge-text');
          var container = document.getElementById('footer-badge-container');

          function setBadgeContent(isBottom) {
            if(isBottom){
              badge.style.background = "#c7ff35";
              badge.style.color = "#2c2452";
              dot.style.background = "#2e2052";
              dot.style.marginLeft = "8px";
              dot.style.marginRight = "12px";
              text.innerHTML = '<a href="mailto:domain@nic.bn" style="color:#2c2452;text-decoration:none;font-weight:600;">domain@nic.bn</a>';
            }else{
              badge.style.background = "#2c2452";
              badge.style.color = "#fff";
              dot.style.background = "#c0ff2e";
              dot.style.marginLeft = "8px";
              dot.style.marginRight = "12px";
              text.textContent = "Available for freelance";
            }
          }
          function updateBadge(){
            var isBottom = (window.innerHeight + window.scrollY) >= (document.body.offsetHeight - 2);
            container.style.opacity = isBottom ? "1" : "0.92";
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
