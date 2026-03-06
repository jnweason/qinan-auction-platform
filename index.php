<?php
/**
 * 網站首頁
 * 顯示網站介紹和導航連結
 */

// 啟動 Session
session_start();

// 引入必要檔案
include "includes/db_connect.php";
include "includes/functions.php";
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        
        .header {
            background-color: #343a40;
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            margin: 0;
            font-size: 1.5rem;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 15px;
            padding: 8px 12px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .nav-links a:hover {
            background-color: #495057;
        }
        
        .hero {
            background-color: #007cba;
            color: white;
            text-align: center;
            padding: 3rem 1rem;
        }
        
        .hero h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .hero p {
            font-size: 1.2rem;
            max-width: 800px;
            margin: 0 auto 2rem;
        }
        
        .cta-button {
            display: inline-block;
            background-color: #fff;
            color: #007cba;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        
        .cta-button:hover {
            background-color: #e9ecef;
        }
        
        .features {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .features h2 {
            text-align: center;
            color: #343a40;
            margin-bottom: 2rem;
        }
        
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .feature-card {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .feature-card h3 {
            color: #007cba;
            margin-bottom: 1rem;
        }
        
        .footer {
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 2rem;
            margin-top: 3rem;
        }
    </style>
</head>
<body>
    <header class="header">
        <h1><?php echo SITE_NAME; ?></h1>
        <div class="nav-links">
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="admin/dashboard.php">管理後台</a>
                <?php else: ?>
                    <a href="customer/dashboard.php">客戶中心</a>
                <?php endif; ?>
                <a href="logout.php">登出</a>
            <?php else: ?>
                <a href="login.php">登入</a>
                <a href="register.php">註冊</a>
            <?php endif; ?>
        </div>
    </header>
    
    <section class="hero">
        <h2>專業的奇楠沉香交易平台</h2>
        <p>提供最安全、最便捷的奇楠沉香拍賣與交易服務，讓您輕鬆買賣珍貴的奇楠沉香。</p>
        <a href="auction-list.php" class="cta-button">立即查看拍賣</a>
    </section>
    
    <section class="features">
        <h2>平台特色</h2>
        <div class="feature-grid">
            <div class="feature-card">
                <h3>即時拍賣</h3>
                <p>實時顯示最新拍賣資訊，掌握市場動態</p>
            </div>
            <div class="feature-card">
                <h3>專業鑑定</h3>
                <p>專業團隊確保每件商品品質真實可靠</p>
            </div>
            <div class="feature-card">
                <h3>安全交易</h3>
                <p>多重安全保障，讓您安心交易</p>
            </div>
        </div>
    </section>
    
    <footer class="footer">
        <p>&copy; 2024 <?php echo SITE_NAME; ?>. All rights reserved.</p>
    </footer>
</body>
</html>
