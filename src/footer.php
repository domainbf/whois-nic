<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>简洁页脚设计</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }
        
        body {
            background: #f8fafc;
            color: #334155;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 20px;
        }
        
        .container {
            flex: 1;
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
            text-align: center;
        }
        
        h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            color: #1e293b;
        }
        
        p {
            font-size: 1.2rem;
            color: #64748b;
            max-width: 700px;
            margin: 0 auto 30px;
        }
        
        .content {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        
        .footer {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 -4px 6px rgba(0, 0, 0, 0.05);
            margin-top: auto;
            border: 1px solid #e2e8f0;
        }
        
        .nav-links {
            display: flex;
            justify-content: center;
            padding: 20px;
            flex-wrap: wrap;
            gap: 15px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .nav-links a {
            color: #475569;
            text-decoration: none;
            font-size: 1.1rem;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 6px;
            transition: all 0.2s ease;
        }
        
        .nav-links a:hover {
            background: #f1f5f9;
            color: #3b82f6;
        }
        
        .announcement-container {
            padding: 15px 20px;
            text-align: center;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .announcement-title {
            font-size: 1.1rem;
            margin-bottom: 10px;
            color: #3b82f6;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-weight: 600;
        }
        
        .announcement-box {
            background: #f8fafc;
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
            border: 1px solid #e2e8f0;
        }
        
        .announcement {
            font-size: 1rem;
            line-height: 1.5;
            opacity: 0;
            position: absolute;
            width: 90%;
            text-align: center;
            transition: opacity 0.5s ease;
            color: #475569;
        }
        
        .announcement.active {
            opacity: 1;
        }
        
        .host-info {
            text-align: center;
            padding: 15px;
            font-size: 0.95rem;
            color: #64748b;
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
            background: #f1f5f9;
            font-size: 0.9rem;
            color: #64748b;
        }
        
        @media (max-width: 768px) {
            .nav-links {
                gap: 10px;
            }
            
            .nav-links a {
                font-size: 0.95rem;
                padding: 6px 12px;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            p {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>网站内容区域</h1>
        <p>这是您网站的主要内容区域。页脚设计简洁，与参考网站风格一致，不影响页面文字颜色。</p>
        
        <div class="content">
            <h2>主要功能</h2>
            <p>此页面使用浅色背景和深色文字，页脚不会影响页面其他部分的文字颜色。</p>
        </div>
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
            Hosted on <a href="https://vercel.com" rel="noopener" target="_blank">Vercel</a>
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
