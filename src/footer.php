<<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>网站页脚与公告系统</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: #2c3e50;
            color: #fff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 20px;
        }
        
        .container {
            flex: 1;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .footer {
            background: #34495e;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 -3px 15px rgba(0, 0, 0, 0.2);
        }
        
        .nav-links {
            display: flex;
            justify-content: center;
            padding: 20px;
            flex-wrap: wrap;
            gap: 20px;
            background: #2c3e50;
        }
        
        .nav-links a {
            color: #fff;
            text-decoration: none;
            font-size: 1.1rem;
            padding: 8px 16px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .nav-links a:hover {
            background: #3498db;
        }
        
        .announcement-container {
            padding: 15px;
            text-align: center;
            background: #2c3e50;
            border-bottom: 1px solid #39546d;
        }
        
        .announcement-title {
            font-size: 1.2rem;
            margin-bottom: 10px;
            color: #3498db;
        }
        
        .announcement-box {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 15px;
            min-height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            max-width: 700px;
        }
        
        .announcement {
            font-size: 1rem;
            line-height: 1.5;
            opacity: 0;
            transition: opacity 0.5s ease;
            position: absolute;
            text-align: center;
            width: 80%;
        }
        
        .announcement.active {
            opacity: 1;
        }
        
        .host-info {
            text-align: center;
            padding: 15px;
            background: #2c3e50;
            font-size: 0.9rem;
        }
        
        .host-info a {
            color: #3498db;
            text-decoration: none;
        }
        
        .copyright {
            text-align: center;
            padding: 12px;
            background: #2c3e50;
            font-size: 0.9rem;
            color: #aaa;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>网站内容区域</h1>
        <p>这是您网站的主要内容区域。页脚包含导航链接和公告系统。</p>
    </div>
    
    <footer class="footer">
        <div class="nav-links">
            <a href="#">首页</a>
            <a href="#">产品服务</a>
            <a href="#">关于我们</a>
            <a href="#">联系方式</a>
        </div>
        
        <div class="announcement-container">
            <div class="announcement-title">网站公告</div>
            <div class="announcement-box">
                <div class="announcement active">欢迎访问我们的网站！我们刚刚发布了最新版本的系统更新。</div>
                <div class="announcement">服务器将于下周进行维护，预计 downtime 为2小时，请合理安排您的工作。</div>
                <div class="announcement">新功能「域名查询」已上线，欢迎使用并提供宝贵意见！</div>
                <div class="announcement">节日优惠活动正在进行中，注册新用户可享受15%的折扣优惠。</div>
            </div>
        </div>
        
        <div class="host-info">
            Hosted on <a href="https://example.com" rel="noopener" target="_blank">Example Hosting Provider</a>
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
