<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>NIC.BN - 域名查询</title>
    <style>
        /* ====== 基础页面布局 ====== */
        html, body {
            height: 100%;
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        body {
            display: flex;
            flex-direction: column;
            background: #f8fafc;
            color: #333;
        }
        .container {
            flex: 1;
        }

        /* ====== 页脚基础样式 ====== */
        .footer {
            background: #ffffff;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
            padding: 30px 0 20px 0;
        }
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        /* ====== 公告区域（核心修复部分）====== */
        .announcement-container {
            width: 90%;
            max-width: 600px;
            height: 60px; /* 固定高度，避免跳动 */
            position: relative;
            overflow: hidden;
            margin-bottom: 20px;
        }
        .announcement-track {
            position: absolute;
            width: 100%;
            top: 0;
            left: 0;
            transition: transform 0.5s ease-in-out;
        }
        .announcement {
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            font-weight: 600;
            color: #2c2452;
            padding: 0 15px;
            box-sizing: border-box;
            text-align: center;
        }
        .announcement-icon {
            margin-right: 8px;
            animation: bounce 1.5s infinite;
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0) rotate(-5deg); }
            50% { transform: translateY(-2px) rotate(5deg); }
        }

        /* ====== LOGO 样式 ====== */
        .footer-logo {
            width: 180px;
            height: auto;
            margin: 15px 0;
            filter: drop-shadow(0 2px 8px rgba(44, 36, 82, 0.12));
            transition: all 0.3s ease;
        }

        /* ====== 合作徽章样式 ====== */
        .badge {
            display: inline-flex;
            align-items: center;
            background: #2c2452;
            color: #ffffff;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin: 15px 0;
            box-shadow: 0 4px 12px rgba(44, 36, 82, 0.1);
            transition: all 0.3s ease;
        }
        .badge-icon {
            margin-right: 8px;
        }
        .badge.mail {
            background: #c7ff35;
            color: #2c2452;
        }

        /* ====== 版权信息 ====== */
        .copyright {
            margin-top: 15px;
            font-size: 12px;
            color: #718096;
        }

        /* ====== 响应式 ====== */
        @media (max-width: 768px) {
            .announcement {
                font-size: 14px;
                padding: 0 10px;
            }
            .footer-logo {
                width: 120px;
            }
            .badge {
                font-size: 13px;
                padding: 6px 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 主内容区域 -->
    </div>

    <footer class="footer">
        <div class="footer-content">

            <!-- ====== 公告轮播区域（已修复跳动问题）====== -->
            <div class="announcement-container">
                <div class="announcement-track" id="announcementTrack">
                    <div class="announcement">
                        <span class="announcement-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M3 9v6h4l5 5V4L7 9H3z" fill="#2c2452"/>
                                <path d="M16.5 12a4.5 4.5 0 0 0-2.5-4v8a4.5 4.5 0 0 0 2.5-4z" fill="#c7ff35"/>
                            </svg>
                        </span>
                        集成 RDAP+WHOIS 双核驱动提供精准域名注册数据
                    </div>
                    <div class="announcement">
                        <span class="announcement-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M3 9v6h4l5 5V4L7 9H3z" fill="#2c2452"/>
                                <path d="M16.5 12a4.5 4.5 0 0 0-2.5-4v8a4.5 4.5 0 0 0 2.5-4z" fill="#c7ff35"/>
                            </svg>
                        </span>
                        本站提供域名查询服务，不储存任何搜索及查询数据信息
                    </div>
                    <div class="announcement">
                        <span class="announcement-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M3 9v6h4l5 5V4L7 9H3z" fill="#2c2452"/>
                                <path d="M16.5 12a4.5 4.5 0 0 0-2.5-4v8a4.5 4.5 0 0 0 2.5-4z" fill="#c7ff35"/>
                            </svg>
                        </span>
                        在售的域名，可点击下方按钮查看所有域名
                    </div>
                    <div class="announcement">
                        <span class="announcement-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M3 9v6h4l5 5V4L7 9H3z" fill="#2c2452"/>
                                <path d="M16.5 12a4.5 4.5 0 0 0-2.5-4v8a4.5 4.5 0 0 0 2.5-4z" fill="#c7ff35"/>
                            </svg>
                        </span>
                        不记录·不储存·所有搜索查询数据仅保留在您本地浏览器
                    </div>
                </div>
            </div>

            <!-- ====== LOGO ====== -->
            <img class="footer-logo" src="logo.png" alt="NIC.BN Logo" />

            <!-- ====== 合作徽章（点击切换）====== -->
            <div class="badge" id="cooperationBadge">
                <span class="badge-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="10" stroke="#c7ff35" stroke-width="2" fill="#2c2452"/>
                        <rect x="8" y="10" width="8" height="4" rx="1" fill="#c7ff35"/>
                        <circle cx="12" cy="12" r="1" fill="#2c2452"/>
                    </svg>
                </span>
                <span id="badgeText">域名寻求合作</span>
            </div>

            <!-- ====== 版权信息 ====== -->
            <div class="copyright">
                &copy; 2025 NIC.BN. All rights reserved.
            </div>

        </div>
    </footer>

    <script>
        // ====== 公告轮播逻辑（垂直位移，无跳动） ======
        document.addEventListener('DOMContentLoaded', function () {
            const track = document.getElementById('announcementTrack');
            const announcements = document.querySelectorAll('.announcement');
            const height = announcements[0].offsetHeight; // 每条高度固定为 60px
            let currentIndex = 0;

            function updatePosition() {
                track.style.transform = `translateY(-${currentIndex * height}px)`;
            }

            function nextAnnouncement() {
                currentIndex = (currentIndex + 1) % announcements.length;
                updatePosition();
            }

            // 初始化显示第一条
            updatePosition();

            // 每 6 秒切换一次
            setInterval(nextAnnouncement, 6000);

            // ====== 合作徽章切换逻辑 ======
            const badge = document.getElementById('cooperationBadge');
            const badgeText = document.getElementById('badgeText');
            let isMailMode = false;

            badge.addEventListener('click', function () {
                isMailMode = !isMailMode;
                if (isMailMode) {
                    badge.classList.add('mail');
                    badgeText.innerHTML = '<a href="mailto:domain@nic.bn" style="color: inherit; text-decoration: none;">domain@nic.bn</a>';
                } else {
                    badge.classList.remove('mail');
                    badgeText.textContent = '域名寻求合作';
                }
            });
        });
    </script>
</body>
</html>
