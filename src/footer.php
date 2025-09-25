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
            padding: 10px; /* 手机端减少 padding */
        }

        .container {
            flex: 1;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 10px; /* 手机端调整 padding */
        }

        .container h1 {
            font-size: 2.5rem; /* 默认较小字体，适配手机 */
            color: #2d3748;
            font-weight: 700;
            text-align: center;
        }
        
        .footer {
            background: transparent;
            width: 100%;
            text-align: center;
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
            font-size: 0.9rem; /* 手机端字体稍小 */
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
            max-width: 80%; /* 完全自适应 */
            height: auto;
            max-height: 60px; /* 限制最大高度，防止过大 */
        }

        .copyright {
            padding: 8px;
            font-size: 0.8rem;
            color: #718096;
        }
        
        @media (min-width: 769px) {
            .container {
                max-width: 1000px;
                padding: 40px 20px;
            }
            .container h1 {
                font-size: 3rem;
            }
            .announcement-box {
                padding: 10px;
                min-height: 40px;
            }
            .announcement {
                font-size: 1rem;
            }
            .host-info {
                padding: 15px;
                font-size: 1rem;
            }
            .copyright {
                padding: 10px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>NIC.BN</h1>
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
    </script>
</body>
</html>
