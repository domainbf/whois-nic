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
            margin: 0 auto;
            padding: 40px 20px;
            text-align: center;
        }
        
        h1 {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #2d3748;
            font-weight: 700;
        }
        
        p {
            font-size: 1.2rem;
            color: #4a5568;
            max-width: 700px;
            margin: 0 auto 30px;
            line-height: 1.6;
        }
        
        .footer {
            background: transparent;
            margin-top: auto;
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
            text-align: center;
            padding: 15px;
            font-size: 1rem;
            color: #718096;
            margin-bottom: 15px;
        }
        
        .host-info a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }
        
        .host-info a:hover {
            text-decoration: underline;
        }
        
        .nav-links {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            font-size: 1rem;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 20px;
            background: #2d3748;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        
        .nav-links a:hover {
            background: #1a202c;
            transform: translateY(-2px);
        }
        
        .copyright {
            text-align: center;
            padding: 10px;
            font-size: 0.9rem;
            color: #718096;
        }
        
        @media (max-width: 768px) {
            .nav-links {
                gap: 8px;
            }
            
            .nav-links a {
                font-size: 0.9rem;
                padding: 6px 12px;
            }
            
            h1 {
                font-size: 2.5rem;
            }
            
            p {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>NIC.BN</h1>
        <p>有一些域名正在出售，点击下方我的域名查看。</p>
    </div>
    
    <footer class="footer">
        <div class="announcement-container">
            <div class="announcement-box">
                <div class="announcement active">RDAP+WHOIS 双核驱动提供准确信息。</div>
                <div class="announcement">我们提供查询平台，但不储存任何查询数据。</div>
                <div class="announcement">域名出售，更多☝️点击{我的域名}查看，</div>
                <div class="announcement">程序并不完善，欢迎体验并提供宝贵意见！</div>
            </div>
        </div>
        
        <div class="host-info">
            Hosted on <a href="https://vercel.com" rel="noopener" target="_blank">Vercel Platform</a>
        </div>
        
        <div class="nav-links">
            <a href="#">首页</a>
            <a href="https://domain.bf">我的域名</a>
            <a href="#">关于我们</a>
            <a href="#">联系方式</a>
        </div>
        
        <div class="copyright">
            &copy; 2025 NIC.BN. All rights reserved.
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const announcements = document.querySelectorAll('.announcement');
            let currentIndex = 0;
            
            // 初始化显示第一条公告
            announcements.forEach((ann, index) => {
                if (index !== 0) {
                    ann.style.display = 'none';
                }
            });
            
            function showNextAnnouncement() {
                // 隐藏当前公告
                announcements[currentIndex].classList.remove('active');
                setTimeout(() => {
                    announcements[currentIndex].style.display = 'none';
                    
                    // 移动到下一个公告
                    currentIndex = (currentIndex + 1) % announcements.length;
                    
                    // 显示下一个公告
                    announcements[currentIndex].style.display = 'block';
                    setTimeout(() => {
                        announcements[currentIndex].classList.add('active');
                    }, 50);
                }, 500);
            }
            
            // 每6秒切换一次公告
            setInterval(showNextAnnouncement, 6000);
        });
    </script>
</body>
</html>
