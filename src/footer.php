<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>简约透明页脚设计</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
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
            font-size: 2.5rem;
            margin-bottom: 20px;
            color: #2d3748;
        }
        
        p {
            font-size: 1.1rem;
            color: #4a5568;
            max-width: 700px;
            margin: 0 auto 30px;
            line-height: 1.6;
        }
        
        .content {
            background: rgba(255, 255, 255, 0.8);
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
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
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .nav-links a:hover {
            background: #1a202c;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        
        .announcement-container {
            text-align: center;
            margin-bottom: 20px;
            max-width: 100%;
            overflow: hidden;
        }
        
        .announcement-title {
            font-size: 1rem;
            margin-bottom: 8px;
            color: #4a5568;
            font-weight: 600;
        }
        
        .announcement-box {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 8px;
            padding: 12px;
            min-height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            max-width: 100%;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .announcement {
            font-size: 0.95rem;
            line-height: 1.5;
            opacity: 0;
            position: absolute;
            width: 90%;
            text-align: center;
            transition: opacity 0.5s ease;
            color: #2d3748;
        }
        
        .announcement.active {
            opacity: 1;
        }
        
        .host-info {
            text-align: center;
            padding: 10px;
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
            padding: 10px;
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
            
            h1 {
                font-size: 2rem;
            }
            
            p {
                font-size: 1rem;
            }
            
            .announcement {
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 480px) {
            .nav-links {
                flex-direction: column;
                align-items: center;
            }
            
            .nav-links a {
                width: 180px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>简约透明页脚设计</h1>
        <p>这个设计采用了完全透明的页脚背景，导航链接使用黑色标签包裹，公告区域不会超出屏幕宽度。</p>
        
        <div class="content">
            <h2>设计特点</h2>
            <p>透明背景页脚 | 黑色标签导航 | 响应式设计 | 简约风格</p>
            <p>页脚不会影响页面内容布局，保持整体设计的简洁性和一致性。</p>
        </div>
        
        <div class="content">
            <h2>使用说明</h2>
            <p>直接将此代码复制到您的项目中即可使用。所有样式都是自包含的，无需外部依赖。</p>
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
            <div class="announcement-title">网站公告</div>
            <div class="announcement-box">
                <div class="announcement active">欢迎访问我们的网站！新版本已上线，提供更快的查询服务。</div>
                <div class="announcement">系统将于下周进行维护升级，预计停机时间为2小时。</div>
                <div class="announcement">新增批量查询功能现已上线，欢迎体验并提供宝贵意见！</div>
                <div class="announcement">节日优惠活动正在进行中，高级会员可享受更多功能。</div>
            </div>
        </div>
        
        <div class="host-info">
            Hosted on <a href="https://vercel.com" rel="noopener" target="_blank">Vercel Platform</a>
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
