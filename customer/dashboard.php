<?php
/**
 * 客戶儀表板
 * 客戶的主要控制面板
 */

// 引入必要檔案
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// 檢查登入和權限
checkLogin();
if (!isCustomer()) {
    header("Location: ../index.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];

// 取得客戶統計數據
try {
    // 帳戶餘額資訊
    $balance_sql = "SELECT balance FROM users WHERE id = ?";
    $stmt = $conn->prepare($balance_sql);
    $stmt->bind_param("i", $current_user_id);
    $stmt->execute();
    $balance_result = $stmt->get_result();
    $account_balance = $balance_result->fetch_assoc()['balance'] ?? 0;
    
    // 待收取餘額（已結標但未入帳的金額）
    $pending_balance_sql = "SELECT SUM(final_price) as pending_amount 
                           FROM items 
                           WHERE seller_id = ? AND status = 'sold' AND final_price IS NOT NULL";
    $stmt = $conn->prepare($pending_balance_sql);
    $stmt->bind_param("i", $current_user_id);
    $stmt->execute();
    $pending_result = $stmt->get_result();
    $pending_balance = $pending_result->fetch_assoc()['pending_amount'] ?? 0;
    
    // 商品刊登狀態統計
    $items_stats_sql = "SELECT status, COUNT(*) as count 
                       FROM items 
                       WHERE seller_id = ? 
                       GROUP BY status";
    $stmt = $conn->prepare($items_stats_sql);
    $stmt->bind_param("i", $current_user_id);
    $stmt->execute();
    $items_stats_result = $stmt->get_result();
    
    $items_stats = [
        'pending' => 0,
        'active' => 0,
        'sold' => 0,
        'unsold' => 0
    ];
    
    while ($stat = $items_stats_result->fetch_assoc()) {
        $items_stats[$stat['status']] = $stat['count'];
    }
    
    // 最近刊登的商品
    $recent_items_sql = "SELECT * FROM items 
                        WHERE seller_id = ? 
                        ORDER BY created_at DESC 
                        LIMIT 5";
    $stmt = $conn->prepare($recent_items_sql);
    $stmt->bind_param("i", $current_user_id);
    $stmt->execute();
    $recent_items_result = $stmt->get_result();
    
    // 最近的出價記錄（客戶作為出價者）
    $recent_bids_sql = "SELECT b.*, i.name as item_name 
                       FROM bids b 
                       JOIN items i ON b.item_id = i.id 
                       WHERE b.bidder_id = ? 
                       ORDER BY b.bid_time DESC 
                       LIMIT 5";
    $stmt = $conn->prepare($recent_bids_sql);
    $stmt->bind_param("i", $current_user_id);
    $stmt->execute();
    $recent_bids_result = $stmt->get_result();
    
} catch (Exception $e) {
    $error_message = "載入數據時發生錯誤：" . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>客戶儀表板 - <?php echo SITE_NAME; ?></title>
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
        
        .balance-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .balance-card {
            background-color: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .balance-card h3 {
            color: #6c757d;
            margin: 0 0 1rem 0;
            font-size: 1rem;
        }
        
        .balance-amount {
            font-size: 2rem;
            font-weight: bold;
        }
        
        .current-balance {
            color: #007cba;
        }
        
        .pending-balance {
            color: #ffc107;
        }
        
        .items-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
        }
        
        .pending-stat {
            color: #ffc107;
        }
        
        .active-stat {
            color: #28a745;
        }
        
        .sold-stat {
            color: #007bff;
        }
        
        .unsold-stat {
            color: #dc3545;
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
        
        .add-item-btn {
            background-color: #28a745;
            color: white;
            padding: 0.5rem 1rem;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
            display: inline-block;
            margin-bottom: 1rem;
        }
        
        .add-item-btn:hover {
            background-color: #218838;
        }
        
        @media (max-width: 768px) {
            .balance-section,
            .items-stats {
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
        <h1><?php echo SITE_NAME; ?> - 客戶儀表板</h1>
        <div class="nav-links">
            <a href="../index.php">首頁</a>
            <a href="my-items.php">我的商品</a>
            <a href="profile.php">個人資料</a>
            <a href="../logout.php">登出</a>
        </div>
    </header>
    
    <div class="container">
        <div class="dashboard-header">
            <h2>歡迎回來，<?php echo safeOutput($_SESSION['username']); ?>！</h2>
            <p>以下是您的帳戶和商品資訊</p>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div style="background-color: #f8d7da; color: #721c24; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
                <?php echo safeOutput($error_message); ?>
            </div>
        <?php endif; ?>
        
        <div class="balance-section">
            <div class="balance-card">
                <h3>目前帳戶餘額</h3>
                <div class="balance-amount current-balance"><?php echo formatCurrency($account_balance); ?></div>
            </div>
            <div class="balance-card">
                <h3>待收取餘額</h3>
                <div class="balance-amount pending-balance"><?php echo formatCurrency($pending_balance); ?></div>
                <div style="font-size: 0.9rem; color: #6c757d; margin-top: 0.5rem;">
                    已結標商品的款項等待確認入帳
                </div>
            </div>
        </div>
        
        <div class="items-stats">
            <div class="stat-card">
                <h3>待刊登</h3>
                <div class="stat-value pending-stat"><?php echo $items_stats['pending']; ?></div>
            </div>
            <div class="stat-card">
                <h3>刊登中</h3>
                <div class="stat-value active-stat"><?php echo $items_stats['active']; ?></div>
            </div>
            <div class="stat-card">
                <h3>已結標</h3>
                <div class="stat-value sold-stat"><?php echo $items_stats['sold']; ?></div>
            </div>
            <div class="stat-card">
                <h3>已流標</h3>
                <div class="stat-value unsold-stat"><?php echo $items_stats['unsold']; ?></div>
            </div>
        </div>
        
        <a href="add-item.php" class="add-item-btn">新增商品</a>
        
        <div class="section">
            <h3>最近刊登的商品</h3>
            <?php if ($recent_items_result && $recent_items_result->num_rows > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>商品名稱</th>
                            <th>狀態</th>
                            <th>開價</th>
                            <th>目前出價</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($item = $recent_items_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo safeOutput($item['name']); ?></td>
                                <td>
                                    <?php 
                                    switch ($item['status']) {
                                        case 'pending': echo '<span style="color:#ffc107">待刊登</span>'; break;
                                        case 'active': echo '<span style="color:#28a745">刊登中</span>'; break;
                                        case 'sold': echo '<span style="color:#007bff">已結標</span>'; break;
                                        case 'unsold': echo '<span style="color:#dc3545">已流標</span>'; break;
                                        default: echo '未知';
                                    }
                                    ?>
                                </td>
                                <td><?php echo formatCurrency($item['start_price']); ?></td>
                                <td><?php echo $item['current_bid'] > 0 ? formatCurrency($item['current_bid']) : '無'; ?></td>
                                <td class="action-links">
                                    <a href="edit-item.php?id=<?php echo $item['id']; ?>">編輯</a>
                                    <a href="upload-images.php?item_id=<?php echo $item['id']; ?>">圖片</a>
                                    <a href="../item-detail.php?id=<?php echo $item['id']; ?>">查看</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">您還沒有刊登任何商品</div>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h3>最近的出價記錄</h3>
            <?php if ($recent_bids_result && $recent_bids_result->num_rows > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>商品名稱</th>
                            <th>出價金額</th>
                            <th>出價時間</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($bid = $recent_bids_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo safeOutput($bid['item_name']); ?></td>
                                <td><?php echo formatCurrency($bid['amount']); ?></td>
                                <td><?php echo formatDateTime($bid['bid_time']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">您還沒有對任何商品出價</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
