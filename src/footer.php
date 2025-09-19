<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>简约透明页脚</title>
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
        }
        
        .footer {
            background: transparent;
            margin-top: auto;
        }
        
        .nav-links {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
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
        
        .announcement-container {
            text-align: center;
            margin-bottom: 15px;
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
            font-size: 0.95rem;
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
            padding: 8px;
            font-size: 0.9rem;
            color: #718096;
        }
        
        .host-info a {
            color: #4a5568;
            text-decoration: none;
            font-weight: 500;
        }
        
        .host-info a:hover {
            text-decoration: underline;
        }
        
        .copyright {
            text-align: center;
            padding: 8px;
            font-size: 0.85rem;
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
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 页面内容区域 -->
        <h1>网站主要内容</h1>
        <p>这里是您网站的主要内容区域。页脚将显示在页面底部。</p>
    </div>
    
    <footer class="footer">
        <div class="nav-links">
            <a href="#">首页</a>
            <a href="https://domain.bf">我的域名</a>
            <a href="#">关于我们</a>
            <a href="#">联系方式</a>
        </div>
        
        <div class="announcement-container">
            <div class="announcement-box">
                <div class="announcement active">RDAP+WHOIS双模式下择最有结果显示。</div>
                <div class="announcement">我们提供查询平台，但不储存任何数据</div>
                <div class="announcement">此域名正在出售，更多域名请点上方☝️{我的域名}查看，</div>
                <div class="announcement">程序并不完善，欢迎体验并提供宝贵意见！</div>
            </div>
        </div>
        
        <div class="host-info">
            Hosted on <a href="https://vercel.com" rel="noopener" target="_blank">Vercel Platform</a>
        </div>
        
        <div class="copyright">
            &copy; 2023 Your Website Name. All rights reserved.
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
