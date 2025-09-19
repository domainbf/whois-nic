<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>精简页脚设计</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: #1a2238;
            color: #e2e8f0;
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
            text-align: center;
        }
        
        .footer {
            background: rgba(15, 23, 42, 0.95);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 -5px 25px rgba(0, 0, 0, 0.4);
            margin-top: 30px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .nav-links {
            display: flex;
            justify-content: center;
            padding: 20px;
            flex-wrap: wrap;
            gap: 15px;
            background: rgba(30, 41, 59, 0.9);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .nav-links a {
            color: #e2e8f0;
            text-decoration: none;
            font-size: 1.1rem;
            font-weight: 500;
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .nav-links a:hover {
            background: rgba(56, 189, 248, 0.15);
            color: #3b82f6;
        }
        
        .announcement-container {
            padding: 15px 20px;
            text-align: center;
            background: rgba(30, 41, 59, 0.8);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .announcement-title {
            font-size: 1.2rem;
            margin-bottom: 10px;
            color: #3b82f6;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .announcement-box {
            background: rgba(15, 23, 42, 0.7);
            border-radius: 8px;
            padding: 15px;
            min-height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            max-width: 700px;
            position: relative;
            overflow: hidden;
        }
        
        .announcement {
            font-size: 1rem;
            line-height: 1.5;
            opacity: 0;
            position: absolute;
            width: 90%;
            text-align: center;
            transition: opacity 0.5s ease;
            color: #cbd5e1;
        }
        
        .announcement.active {
            opacity: 1;
        }
        
        .host-info {
            text-align: center;
            padding: 15px;
            background: rgba(30, 41, 59, 0.9);
            font-size: 0.95rem;
            color: #94a3b8;
        }
        
        .host-info a {
            color: #3b82f6;
            text-decoration: none;
        }
        
        .host-info a:hover {
            text-decoration: underline;
        }
        
        .copyright {
            text-align: center;
            padding: 15px;
            background: rgba(15, 23, 42, 0.9);
            font-size: 0.9rem;
            color: #64748b;
        }
        
        @media (max-width: 768px) {
            .nav-links {
                gap: 10px;
            }
            
            .nav-links a {
                font-size: 0.95rem;
                padding: 8px 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>网站内容区域</h1>
        <p>这是您网站的主要内容区域。下方页脚包含导航链接和公告系统。</p>
    </div>
    
    <footer class="footer">
        <div class="nav-links">
            <a href="#">首页</a>
            <a href="#">产品服务</a>
            <a href="#">关于我们</a>
            <a href="#">联系方式</a>
        </div>
        
        <div class="announcement-container">
            <div class="announcement-title">
                <span>📢</span>
                系统公告
            </div>
            <div class="announcement-box">
                <div class="announcement active">欢迎使用我们的服务！新版本已上线，查询速度提升30%。</div>
                <div class="announcement">系统将于下周进行维护升级，预计停机时间为2小时。</div>
                <div class="announcement">新增批量查询功能现已上线，欢迎体验并提供宝贵意见！</div>
                <div class="announcement">节日优惠活动正在进行中，高级会员可享受无限次查询功能。</div>
            </div>
        </div>
        
        <div class="host-info">
            Hosted on <a href="https://example.com" rel="noopener" target="_blank">Example Hosting</a>
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
                    
                    // 移动到下一个
