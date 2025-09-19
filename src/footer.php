<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Whois 域名查询 - 专业域名信息查询工具</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', 'PingFang SC', 'Microsoft YaHei', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #e2e8f0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 20px;
            line-height: 1.6;
        }
        
        .container {
            flex: 1;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            width: 100%;
        }
        
        header {
            text-align: center;
            padding: 40px 0;
            margin-bottom: 30px;
        }
        
        h1 {
            font-size: 2.8rem;
            margin-bottom: 15px;
            background: linear-gradient(90deg, #3b82f6 0%, #6366f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .description {
            font-size: 1.2rem;
            max-width: 800px;
            margin: 0 auto 30px;
            color: #94a3b8;
        }
        
        .main-content {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .search-box {
            background: rgba(15, 23, 42, 0.7);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .search-box h2 {
            margin-bottom: 20px;
            color: #e2e8f0;
            font-size: 1.8rem;
        }
        
        .search-form {
            display: flex;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .search-input {
            flex: 1;
            padding: 15px 20px;
            border: none;
            border-radius: 8px 0 0 8px;
            background: #1e293b;
            color: #e2e8f0;
            font-size: 1.1rem;
            border: 1px solid #334155;
        }
        
        .search-button {
            padding: 15px 25px;
            border: none;
            border-radius: 0 8px 8px 0;
            background: linear-gradient(90deg, #3b82f6 0%, #6366f1 100%);
            color: white;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .search-button:hover {
            background: linear-gradient(90deg, #2563eb 0%, #4f46e5 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .feature-card {
            background: rgba(15, 23, 42, 0.7);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: transform 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        
        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: #3b82f6;
        }
        
        .feature-card h3 {
            margin-bottom: 10px;
            color: #e2e8f0;
        }
        
        .feature-card p {
            color: #94a3b8;
        }
        
        .footer {
            background: rgba(15, 23, 42, 0.9);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 -5px 25px rgba(0, 0, 0, 0.4);
            margin-top: auto;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .nav-links {
            display: flex;
            justify-content: center;
            padding: 25px 20px;
            flex-wrap: wrap;
            gap: 15px;
            background: rgba(15, 23, 42, 0.8);
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
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .nav-links a:hover {
            background: rgba(56, 189, 248, 0.1);
            color: #3b82f6;
        }
        
        .announcement-container {
            padding: 20px;
            text-align: center;
            background: rgba(15, 23, 42, 0.6);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .announcement-title {
            font-size: 1.3rem;
            margin-bottom: 15px;
            color: #3b82f6;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .announcement-box {
            background: rgba(30, 41, 59, 0.7);
            border-radius: 12px;
            padding: 20px;
            min-height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            max-width: 800px;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .announcement {
            font-size: 1.1rem;
            line-height: 1.6;
            opacity: 0;
            position: absolute;
            width: 90%;
            text-align: center;
            transition: opacity 0.8s ease;
            color: #cbd5e1;
        }
        
        .announcement.active {
            opacity: 1;
        }
        
        .host-info {
            text-align: center;
            padding: 20px;
            background: rgba(15, 23, 42, 0.8);
            font-size: 1rem;
            color: #94a3b8;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .host-info a {
            color: #3b82f6;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .host-info a:hover {
            color: #60a5fa;
            text-decoration: underline;
        }
        
        .copyright {
            text-align: center;
            padding: 18px;
            background: rgba(15, 23, 42, 0.9);
            font-size: 0.95rem;
            color: #64748b;
        }
        
        @media (max-width: 768px) {
            h1 {
                font-size: 2.2rem;
            }
            
            .description {
                font-size: 1rem;
            }
            
            .nav-links {
                gap: 10px;
            }
            
            .nav-links a {
                font-size: 0.95rem;
                padding: 8px 15px;
            }
            
            .search-form {
                flex-direction: column;
            }
            
            .search-input {
                border-radius: 8px;
                margin-bottom: 10px;
            }
            
            .search-button {
                border-radius: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Whois 域名查询</h1>
            <p class="description">专业的域名信息查询工具，提供全面、准确、快速的域名Whois信息查询服务</p>
        </header>
        
        <div class="main-content">
            <div class="search-box">
                <h2>查询域名信息</h2>
                <form class="search-form">
                    <input type="text" class="search-input" placeholder="输入域名（例如：example.com）">
                    <button type="submit" class="search-button">查询</button>
                </form>
            </div>
            
            <h2>服务特点</h2>
            <div class="features">
                <div class="feature-card">
                    <div class="feature-icon">🔍</div>
                    <h3>全面信息</h3>
                    <p>提供完整的域名注册信息，包括注册人、注册商、注册日期和过期日期等</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">⚡</div>
                    <h3>快速查询</h3>
                    <p>优化的查询算法，毫秒级响应速度，快速获取域名信息</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🌐</div>
                    <h3>全球覆盖</h3>
                    <p>支持全球所有通用顶级域(gTLD)和国家代码顶级域(ccTLD)</p>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="footer">
        <div class="nav-links">
            <a href="#">
                <span>🏠</span>
                首页
            </a>
            <a href="#">
                <span>ℹ️</span>
                关于我们
            </a>
            <a href="#">
                <span>🔧</span>
                服务功能
            </a>
            <a href="#">
                <span>📞</span>
                联系我们
            </a>
        </div>
        
        <div class="announcement-container">
            <div class="announcement-title">
                <span>📢</span>
                系统公告
            </div>
            <div class="announcement-box">
                <div class="announcement active">欢迎使用Whois域名查询服务！我们刚刚发布了新版本，查询速度提升30%。</div>
                <div class="announcement">系统将于下周进行维护升级，预计停机时间为2小时，请合理安排查询时间。</div>
                <div class="announcement">新增批量查询功能现已上线，欢迎体验并提供宝贵意见！</div>
                <div class="announcement">节日优惠活动正在进行中，高级会员可享受无限次查询和导出功能。</div>
            </div>
        </div>
        
        <div class="host-info">
            Hosted on <a href="https://vercel.com" rel="noopener" target="_blank">Vercel</a>
        </div>
        
        <div class="copyright">
            &copy; 2023 Whois域名查询服务. 保留所有权利.
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
            
            // 搜索表单提交处理
            const searchForm = document.querySelector('.search-form');
            searchForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const input = this.querySelector('.search-input');
                const domain = input.value.trim();
                
                if (domain) {
                    alert(`正在查询: ${domain}\n演示功能 - 实际实现需连接后端API`);
                    input.value = '';
                }
            });
        });
    </script>
</body>
</html>
