<?php
/**
 * 管理員儀表板
 * 管理員的主要控制面板
 */

// 引入必要檔案
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// 檢查登入和權限
checkLogin();
if (!isAdmin()) {
    header("Location: ../index.php");
    exit();
}

// 取得統計數據
try {
    // 總商品數
    $total_items_sql = "SELECT COUNT(*) as count FROM items";
    $total_items_result = $conn->query($total_items_sql);
    $total_items = $total_items_result->fetch_assoc()['count'];
    
    // 總用戶數
    $total_users_sql = "SELECT COUNT(*) as count FROM users WHERE role = 'customer'";
    $total_users_result = $conn->query($total_users_sql);
    $total_users = $total_users_result->fetch_assoc()['count'];
    
    // 正在拍賣的商品數
    $active_items_sql = "SELECT COUNT(*) as count FROM items WHERE status = 'active'";
    $active_items_result = $conn->query($active_items_sql);
    $active_items = $active_items_result->fetch_assoc()['count'];
    
    // 總交易金額（已結標商品）
    $total_sales_sql = "SELECT SUM(final_price) as total FROM items WHERE status = 'sold' AND final_price IS NOT NULL";
    $total_sales_result = $conn->query($total_sales_sql);
    $total_sales = $total_sales_result->fetch_assoc()['total'] ?? 0;
    
    // 最近的出價記錄
    $recent_bids_sql = "SELECT b.*, i.name as item_name, u.username as bidder_name 
                        FROM bids b 
                        JOIN items i ON b.item_id = i.id 
                        JOIN users u ON b.bidder_id = u.id 
                        ORDER BY b.bid_time DESC 
                        LIMIT 10";
    $recent_bids_result = $conn->query($recent_bids_sql);
    
    // 最近上傳的商品
    $recent_items_sql = "SELECT i.*, u.username as seller_name 
                         FROM items i 
                         JOIN users u ON i.seller_id = u.id 
                         ORDER BY i.created_at DESC 
                         LIMIT 10";
    $recent_items_result = $conn->query($recent_items_sql);
    
} catch (Exception $e) {
    $error_message = "載入數據時發生錯誤：" . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理員儀表板 - <?php echo SITE_NAME; ?></title>
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
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .dashboard-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .dashboard-header h2 {
            color: #343a40;
            margin-bottom: 0.5rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background-color: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            color: #6c757d;
            margin: 0 0 1rem 0;
            font-size: 1rem;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #007cba;
        }
        
        .section {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .section h3 {
            color: #343a40;
            margin-top: 0;
            border-bottom: 2px solid #007cba;
            padding-bottom: 0.5rem;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            background-color: #f8f9fa;
            padding: 0.75rem;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
        }
        
        .data-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #dee2e6;
        }
        
        .data-table tr:last-child td {
            border-bottom: none;
        }
        
        .data-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .action-links a {
            color: #007cba;
            text-decoration: none;
            margin-right: 10px;
        }
        
        .action-links a:hover {
            text-decoration: underline;
        }
        
        .no-data {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 1rem;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .data-table {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <h1><?php echo SITE_NAME; ?> - 管理員儀表板</h1>
        <div class="nav-links">
            <a href="../index.php">返回首頁</a>
            <a href="manage-items.php">商品管理</a>
            <a href="manage-users.php">用戶管理</a>
            <a href="reports.php">報表查詢</a>
            <a href="../logout.php">登出</a>
        </div>
    </header>
    
    <div class="container">
        <div class="dashboard-header">
            <h2>歡迎回來，<?php echo safeOutput($_SESSION['username']); ?>！</h2>
            <p>以下是平台的最新統計數據</p>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div style="background-color: #f8d7da; color: #721c24; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
                <?php echo safeOutput($error_message); ?>
            </div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>總商品數</h3>
                <div class="stat-value"><?php echo $total_items; ?></div>
            </div>
            <div class="stat-card">
                <h3>總用戶數</h3>
                <div class="stat-value"><?php echo $total_users; ?></div>
            </div>
            <div class="stat-card">
                <h3>正在拍賣</h3>
                <div class="stat-value"><?php echo $active_items; ?></div>
            </div>
            <div class="stat-card">
                <h3>總交易金額</h3>
                <div class="stat-value"><?php echo formatCurrency($total_sales); ?></div>
            </div>
        </div>
        
        <div class="section">
            <h3>最近出價記錄</h3>
            <?php if ($recent_bids_result && $recent_bids_result->num_rows > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>商品名稱</th>
                            <th>出價者</th>
                            <th>出價金額</th>
                            <th>出價時間</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($bid = $recent_bids_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo safeOutput($bid['item_name']); ?></td>
                                <td><?php echo safeOutput($bid['bidder_name']); ?></td>
                                <td><?php echo formatCurrency($bid['amount']); ?></td>
                                <td><?php echo formatDateTime($bid['bid_time']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">暫無出價記錄</div>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h3>最近上傳商品</h3>
            <?php if ($recent_items_result && $recent_items_result->num_rows > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>商品名稱</th>
                            <th>賣家</th>
                            <th>狀態</th>
                            <th>建立時間</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($item = $recent_items_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo safeOutput($item['name']); ?></td>
                                <td><?php echo safeOutput($item['seller_name']); ?></td>
                                <td>
                                    <?php 
                                    switch ($item['status']) {
                                        case 'pending': echo '待刊登'; break;
                                        case 'active': echo '刊登中'; break;
                                        case 'sold': echo '已結標'; break;
                                        case 'unsold': echo '已流標'; break;
                                        default: echo '未知';
                                    }
                                    ?>
                                </td>
                                <td><?php echo formatDateTime($item['created_at']); ?></td>
                                <td class="action-links">
                                    <a href="edit-item.php?id=<?php echo $item['id']; ?>">編輯</a>
                                    <a href="view-item.php?id=<?php echo $item['id']; ?>">查看</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">暫無商品記錄</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
