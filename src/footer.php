<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            padding: 20px;
        }

        .container {
            flex: 1;
            max-width: 1000px;
            margin: auto;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .container h1 {
            font-size: 3rem;
            color: #2d3748;
            font-weight: 700;
        }
        
        .footer {
            background: transparent;
            margin-top: auto;
            text-align: center;
        }
        
        .announcement-container {
            text-align: center;
            margin-bottom: 20px;
            max-width: 100%;
            overflow: hidden;
        }
        
        .announcement-box {
            padding: 10px;
            min-height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            max-width: 100%;
        }
        
        .announcement {
            font-size: 1rem;
            line-height: 1.5;
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
            padding: 15px;
            font-size: 1rem;
            color: #718096;
            margin-bottom: 15px;
        }
        
        .host-info a img {
            vertical-align: middle;
            width: 500px; /* 调整宽度 */
            height: 100px; /* 调整高度 */
        }

        .copyright {
            padding: 10px;
            font-size: 0.9rem;
            color: #718096;
        }
        
        @media (max-width: 768px) {
            .container h1 {
                font-size: 2.5rem;
            }
            .host-info a img {
                width: 75px; /* 响应式调整宽度 */
                height: 75px; /* 响应式调整高度 */
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
