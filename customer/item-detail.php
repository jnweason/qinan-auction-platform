<?php
/**
 * 商品詳細資訊頁面
 * 顯示單一拍賣商品的詳細資訊和圖片
 */

// 啟動 Session
session_start();

// 引入必要檔案
include "includes/db_connect.php";
include "includes/auth_check.php";
include "includes/functions.php";

// 取得商品 ID
$item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 檢查商品 ID 是否有效
if ($item_id <= 0) {
    header("Location: auction-list.php");
    exit();
}

// 查詢商品詳細資訊
$sql = "SELECT i.*, u.username as seller_name 
        FROM items i 
        LEFT JOIN users u ON i.seller_id = u.id 
        WHERE i.id = ? AND i.status IN ('pending', 'active', 'sold', 'unsold')";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();

// 檢查商品是否存在
if ($result->num_rows == 0) {
    header("Location: auction-list.php");
    exit();
}

$item = $result->fetch_assoc();

// 查詢商品圖片
$image_sql = "SELECT file_path FROM images WHERE item_id = ?";
$stmt = $conn->prepare($image_sql);
$stmt->bind_param("i", $item_id);
$stmt->execute();
$images_result = $stmt->get_result();

// 查詢出價記錄
$bid_sql = "SELECT b.*, u.username as bidder_name 
            FROM bids b 
            LEFT JOIN users u ON b.bidder_id = u.id 
            WHERE b.item_id = ? 
            ORDER BY b.bid_time DESC";

$stmt = $conn->prepare($bid_sql);
$stmt->bind_param("i", $item_id);
$stmt->execute();
$bids_result = $stmt->get_result();

// 處理出價表單提交
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bid_amount']) && isset($_SESSION['user_id'])) {
    $bid_amount = floatval($_POST['bid_amount']);
    
    // 驗證出價金額
    if ($bid_amount <= 0) {
        $error_message = '出價金額必須大於 0';
    } elseif ($bid_amount <= $item['current_bid']) {
        $error_message = '出價金額必須高於目前最高價';
    } else {
        try {
            // 插入出價記錄
            $insert_sql = "INSERT INTO bids (item_id, bidder_id, amount) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("iid", $item_id, $_SESSION['user_id'], $bid_amount);
            
            if ($stmt->execute()) {
                // 更新商品目前最高價
                $update_sql = "UPDATE items SET current_bid = ? WHERE id = ?";
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param("di", $bid_amount, $item_id);
                $stmt->execute();
                
                $success_message = '出價成功！';
                
                // 重新載入商品資訊
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $item_id);
                $stmt->execute();
                $item = $stmt->get_result()->fetch_assoc();
                
                // 重新載入出價記錄
                $stmt = $conn->prepare($bid_sql);
                $stmt->bind_param("i", $item_id);
                $stmt->execute();
                $bids_result = $stmt->get_result();
            } else {
                $error_message = '出價失敗，請稍後再試';
            }
        } catch (Exception $e) {
            $error_message = '出價時發生錯誤，請稍後再試';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo safeOutput($item['name']); ?> - <?php echo SITE_NAME; ?></title>
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
        
        .back-link {
            display: inline-block;
            margin-bottom: 1rem;
            color: #007cba;
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .item-detail {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .images-section {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .main-image {
            width: 100%;
            max-height: 400px;
            object-fit: contain;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }
        
        .thumbnail-images {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .thumbnail {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .thumbnail:hover {
            border-color: #007cba;
        }
        
        .info-section h2 {
            color: #343a40;
            margin-top: 0;
        }
        
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .info-table th {
            text-align: left;
            padding: 0.5rem 0;
            width: 120px;
            color: #495057;
        }
        
        .info-table td {
            padding: 0.5rem 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .price-info {
            background-color: #e9f7ef;
            padding: 1rem;
            border-radius: 4px;
            margin: 1rem 0;
        }
        
        .price-info .label {
            font-weight: bold;
            color: #28a745;
        }
        
        .bid-form {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 4px;
            margin-top: 1rem;
        }
        
        .bid-form h3 {
            margin-top: 0;
            color: #343a40;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ced4da;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        .btn {
            background-color: #007cba;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
        }
        
        .btn:hover {
            background-color: #0056b3;
        }
        
        .btn:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }
        
        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        
        .error {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }
        
        .success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }
        
        .bids-section {
            margin-top: 2rem;
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .bids-section h3 {
            color: #343a40;
            margin-top: 0;
        }
        
        .bids-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .bids-table th {
            background-color: #f8f9fa;
            padding: 0.75rem;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
        }
        
        .bids-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #dee2e6;
        }
        
        .bids-table tr:last-child td {
            border-bottom: none;
        }
        
        @media (max-width: 768px) {
            .item-detail {
                grid-template-columns: 1fr;
            }
            
            .thumbnail-images {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <h1><?php echo SITE_NAME; ?></h1>
        <div class="nav-links">
            <a href="index.php">首頁</a>
            <a href="auction-list.php">拍賣列表</a>
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
    
    <div class="container">
        <a href="auction-list.php" class="back-link">&larr; 回到拍賣列表</a>
        
        <?php if ($error_message): ?>
            <div class="message error"><?php echo safeOutput($error_message); ?></div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="message success"><?php echo safeOutput($success_message); ?></div>
        <?php endif; ?>
        
        <div class="item-detail">
            <div class="images-section">
                <?php if ($images_result->num_rows > 0): ?>
                    <?php $first_image = $images_result->fetch_assoc(); ?>
                    <img src="<?php echo $first_image['file_path']; ?>" alt="<?php echo safeOutput($item['name']); ?>" class="main-image" id="mainImage">
                    
                    <?php if ($images_result->num_rows > 1): ?>
                        <div class="thumbnail-images">
                            <?php 
                            // 重新執行查詢以取得所有圖片
                            $stmt = $conn->prepare($image_sql);
                            $stmt->bind_param("i", $item_id);
                            $stmt->execute();
                            $all_images_result = $stmt->get_result();
                            
                            while ($image_row = $all_images_result->fetch_assoc()): ?>
                                <img src="<?php echo $image_row['file_path']; ?>" 
                                     alt="縮圖" 
                                     class="thumbnail" 
                                     onclick="changeMainImage('<?php echo $image_row['file_path']; ?>')">
                            <?php endwhile; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 2rem; background-color: #f8f9fa; border-radius: 4px;">
                        <p>暫無圖片</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="info-section">
                <h2><?php echo safeOutput($item['name']); ?></h2>
                
                <table class="info-table">
                    <tr>
                        <th>編號：</th>
                        <td><?php echo $item['id']; ?></td>
                    </tr>
                    <tr>
                        <th>賣家：</th>
                        <td><?php echo safeOutput($item['seller_name']); ?></td>
                    </tr>
                    <tr>
                        <th>產地：</th>
                        <td><?php echo safeOutput($item['origin']); ?></td>
                    </tr>
                    <tr>
                        <th>重量：</th>
                        <td><?php echo $item['weight']; ?> 克</td>
                    </tr>
                    <tr>
                        <th>狀態：</th>
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
                    </tr>
                </table>
                
                <div class="price-info">
                    <div><span class="label">開價：</span> <?php echo formatCurrency($item['start_price']); ?></div>
                    <div><span class="label">預期結標金額：</span> <?php echo formatCurrency($item['reserve_price']); ?></div>
                    <div><span class="label">目前最高價：</span> 
                        <?php if ($item['current_bid'] > 0): ?>
                            <?php echo formatCurrency($item['current_bid']); ?>
                        <?php else: ?>
                            尚未有人出價
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($item['remarks']): ?>
                    <div>
                        <strong>其他備註：</strong>
                        <p><?php echo nl2br(safeOutput($item['remarks'])); ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['user_id']) && $item['status'] === 'active' && $_SESSION['user_id'] != $item['seller_id']): ?>
                    <div class="bid-form">
                        <h3>我要出價</h3>
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="bid_amount">出價金額：</label>
                                <input type="number" 
                                       id="bid_amount" 
                                       name="bid_amount" 
                                       step="0.01" 
                                       min="<?php echo ($item['current_bid'] > 0) ? ($item['current_bid'] + 0.01) : 0.01; ?>" 
                                       placeholder="請輸入出價金額"
                                       required>
                            </div>
                            <button type="submit" class="btn">送出出價</button>
                        </form>
                    </div>
                <?php elseif ($item['status'] !== 'active'): ?>
                    <div style="background-color: #fff3cd; padding: 1rem; border-radius: 4px; border: 1px solid #ffeaa7;">
                        <strong>注意：</strong>此商品目前不在拍賣中。
                    </div>
                <?php elseif (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $item['seller_id']): ?>
                    <div style="background-color: #d1ecf1; padding: 1rem; border-radius: 4px; border: 1px solid #bee5eb;">
                        <strong>提示：</strong>您是此商品的賣家，無法對自己的商品出價。
                    </div>
                <?php elseif (!isset($_SESSION['user_id'])): ?>
                    <div style="background-color: #f8d7da; padding: 1rem; border-radius: 4px; border: 1px solid #f5c6cb;">
                        <strong>提示：</strong>請先<a href="login.php">登入</a>才能出價。
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="bids-section">
            <h3>出價記錄</h3>
            <?php if ($bids_result->num_rows > 0): ?>
                <table class="bids-table">
                    <thead>
                        <tr>
                            <th>出價者</th>
                            <th>出價金額</th>
                            <th>出價時間</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($bid = $bids_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo safeOutput($bid['bidder_name']); ?></td>
                                <td><?php echo formatCurrency($bid['amount']); ?></td>
                                <td><?php echo formatDateTime($bid['bid_time']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>目前尚無出價記錄。</p>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function changeMainImage(imagePath) {
            document.getElementById('mainImage').src = imagePath;
        }
    </script>
</body>
</html>
