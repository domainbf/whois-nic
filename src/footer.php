<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>网站页脚与公告系统</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #1a2a6c, #b21f1f, #fdbb2d);
            color: #fff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            text-align: center;
            padding: 30px 0;
        }
        
        h1 {
            font-size: 2.8rem;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .description {
            font-size: 1.2rem;
            max-width: 800px;
            margin: 0 auto 30px;
            line-height: 1.6;
        }
        
        .main-content {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }
        
        .footer {
            background: rgba(0, 0, 0, 0.7);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 -5px 25px rgba(0, 0, 0, 0.4);
        }
        
        .nav-links {
            display: flex;
            justify-content: center;
            padding: 25px 20px;
            flex-wrap: wrap;
            gap: 30px;
            background: rgba(0, 0, 0, 0.8);
        }
        
        .nav-links a {
            color: #fff;
            text-decoration: none;
            font-size: 1.3rem;
            font-weight: 500;
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-3px);
        }
        
        .announcement-container {
            padding: 20px;
            text-align: center;
            background: rgba(0, 0, 0, 0.6);
        }
        
        .announcement-title {
            font-size: 1.4rem;
            margin-bottom: 15px;
            color: #fdbb2d;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .announcement-box {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 20px;
            min-height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            max-width: 800px;
            box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.3);
        }
        
        .announcement {
            font-size: 1.2rem;
            line-height: 1.6;
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.8s ease, transform 0.8s ease;
            position: absolute;
            width: 80%;
        }
        
        .announcement.active {
            opacity: 1;
            transform: translateY(0);
        }
        
        .host-info {
            text-align: center;
            padding: 20px;
            background: rgba(0, 0, 0, 0.8);
            font-size: 1.1rem;
        }
        
        .host-info a {
            color: #4da6ff;
            text-decoration: none;
        }
        
        .host-info a:hover {
            text-decoration: underline;
        }
        
        .copyright {
            text-align: center;
            padding: 15px;
            background: rgba(0, 0, 0, 0.9);
            font-size: 1rem;
            color: #aaa;
        }
        
        @media (max-width: 768px) {
            .nav-links {
                gap: 15px;
            }
            
            .nav-links a {
                font-size: 1.1rem;
                padding: 8px 15px;
            }
            
            h1 {
                font-size: 2.2rem;
            }
            
            .description {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>网站页脚与公告系统</h1>
            <p class="description">这是一个现代化的网站页脚设计，包含横向导航链接和自动循环的公告系统。公告每6秒自动切换，提供良好的用户体验。</p>
        </header>
        
        <div class="main-content">
            <h2>主要功能</h2>
            <p>此设计包含以下元素：</p>
            <ul>
                <li>响应式设计，适应各种屏幕尺寸</li>
                <li>四个横向排列的导航链接，带有悬停效果</li>
                <li>公告区域，每6秒自动切换内容</li>
                <li>平滑的动画过渡效果</li>
                <li>托管信息显示区域</li>
            </ul>
        </div>
    </div>
    
    <footer class="footer">
        <div class="nav-links">
            <a href="#"><i class="fas fa-home"></i> 首页</a>
            <a href="#"><i class="fas fa-info-circle"></i> 关于我们</a>
            <a href="#"><i class="fas fa-services"></i> 服务</a>
            <a href="#"><i class="fas fa-envelope"></i> 联系我们</a>
        </div>
        
        <div class="announcement-container">
            <div class="announcement-title">
                <i class="fas fa-bullhorn"></i>
                <span>网站公告</span>
            </div>
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
                }, 800);
            }
            
            // 每6秒切换一次公告
            setInterval(showNextAnnouncement, 6000);
        });
    </script>
</body>
</html>
