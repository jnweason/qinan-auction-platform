<?php
/**
 * 客戶個人資料管理頁面（修改版）
 * 增加手機號碼和電子信箱顯示及修改功能
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

// 查詢用戶資訊
$sql = "SELECT username, phone, email, balance, created_at FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// 初始化變數
$error_message = '';
$success_message = '';

// 處理個人資訊修改表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // 取得表單資料
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    // 驗證輸入資料
    if (empty($phone) || empty($email)) {
        $error_message = '請填寫所有必填欄位';
    } elseif (!isValidEmail($email)) {
        $error_message = '請輸入正確的電子信箱格式';
    } elseif (!preg_match('/^09\d{8}$/', $phone)) {
        $error_message = '請輸入正確的手機號碼格式（09開頭共10碼）';
    } else {
        try {
            // 檢查手機號碼是否被其他人使用
            $check_phone_sql = "SELECT id FROM users WHERE phone = ? AND id != ?";
            $stmt = $conn->prepare($check_phone_sql);
            $stmt->bind_param("si", $phone, $current_user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error_message = '該手機號碼已被其他用戶使用';
            } else {
                // 檢查電子信箱是否被其他人使用
                $check_email_sql = "SELECT id FROM users WHERE email = ? AND id != ?";
                $stmt = $conn->prepare($check_email_sql);
                $stmt->bind_param("si", $email, $current_user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $error_message = '該電子信箱已被其他用戶使用';
                } else {
                    // 更新個人資訊
                    $update_sql = "UPDATE users SET phone = ?, email = ? WHERE id = ?";
                    $stmt = $conn->prepare($update_sql);
                    $stmt->bind_param("ssi", $phone, $email, $current_user_id);
                    
                    if ($stmt->execute()) {
                        $success_message = '個人資訊更新成功！';
                        // 重新載入用戶資訊
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $current_user_id);
                        $stmt->execute();
                        $user = $stmt->get_result()->fetch_assoc();
                    } else {
                        $error_message = '更新個人資訊失敗，請稍後再試';
                    }
                }
            }
        } catch (Exception $e) {
            $error_message = '更新個人資訊時發生錯誤：' . $e->getMessage();
        }
    }
}

// 處理密碼修改表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    // 取得表單資料
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // 驗證輸入資料
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = '請填寫所有密碼欄位';
    } elseif (strlen($new_password) < 6) {
        $error_message = '新密碼至少需要6個字元';
    } elseif ($new_password !== $confirm_password) {
        $error_message = '新密碼確認不一致';
    } else {
        try {
            // 驗證目前密碼是否正確
            $check_sql = "SELECT password FROM users WHERE id = ?";
            $stmt = $conn->prepare($check_sql);
            $stmt->bind_param("i", $current_user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user_data = $result->fetch_assoc();
            
            if (password_verify($current_password, $user_data['password'])) {
                // 更新密碼
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_sql = "UPDATE users SET password = ? WHERE id = ?";
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param("si", $hashed_password, $current_user_id);
                
                if ($stmt->execute()) {
                    $success_message = '密碼更新成功！';
                    // 清空表單資料
                    $_POST = [];
                } else {
                    $error_message = '更新密碼失敗，請稍後再試';
                }
            } else {
                $error_message = '目前密碼不正確';
            }
        } catch (Exception $e) {
            $error_message = '更新密碼時發生錯誤：' . $e->getMessage();
        }
    }
}

// 查詢用戶的交易統計
try {
    // 已結標商品數量和金額
    $sold_stats_sql = "SELECT COUNT(*) as sold_count, SUM(final_price) as total_sales 
                      FROM items 
                      WHERE seller_id = ? AND status = 'sold'";
    $stmt = $conn->prepare($sold_stats_sql);
    $stmt->bind_param("i", $current_user_id);
    $stmt->execute();
    $sold_stats = $stmt->get_result()->fetch_assoc();
    
    // 待刊登商品數量
    $pending_count_sql = "SELECT COUNT(*) as count FROM items WHERE seller_id = ? AND status = 'pending'";
    $stmt = $conn->prepare($pending_count_sql);
    $stmt->bind_param("i", $current_user_id);
    $stmt->execute();
    $pending_count = $stmt->get_result()->fetch_assoc()['count'];
    
    // 刊登中商品數量
    $active_count_sql = "SELECT COUNT(*) as count FROM items WHERE seller_id = ? AND status = 'active'";
    $stmt = $conn->prepare($active_count_sql);
    $stmt->bind_param("i", $current_user_id);
    $stmt->execute();
    $active_count = $stmt->get_result()->fetch_assoc()['count'];
    
    // 最近的交易記錄
    $recent_transactions_sql = "SELECT i.name as item_name, i.final_price, i.status, i.created_at as transaction_time
                               FROM items i
                               WHERE i.seller_id = ?
                               ORDER BY i.created_at DESC
                               LIMIT 10";
    $stmt = $conn->prepare($recent_transactions_sql);
    $stmt->bind_param("i", $current_user_id);
    $stmt->execute();
    $recent_transactions = $stmt->get_result();
    
} catch (Exception $e) {
    $error_message = "載入交易統計時發生錯誤：" . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>個人資料 - <?php echo defined('SITE_NAME') ? SITE_NAME : '奇楠沉香交易拍賣平台'; ?></title>
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
        
        .page-header {
            margin-bottom: 2rem;
        }
        
        .page-header h2 {
            color: #343a40;
            margin: 0;
        }
        
        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        @media (max-width: 768px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .info-section {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .info-section h3 {
            color: #343a40;
            margin-top: 0;
            border-bottom: 2px solid #007cba;
            padding-bottom: 0.5rem;
        }
        
        .info-item {
            margin-bottom: 1rem;
        }
        
        .info-label {
            font-weight: bold;
            color: #495057;
        }
        
        .info-value {
            margin-top: 0.25rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #343a40;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ced4da;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 1rem;
        }
        
        .btn {
            background-color: #007cba;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #0056b3;
        }
        
        .btn-success {
            background-color: #28a745;
        }
        
        .btn-success:hover {
            background-color: #218838;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 4px;
            text-align: center;
        }
        
        .stat-card h4 {
            color: #6c757d;
            margin: 0 0 0.5rem 0;
            font-size: 0.9rem;
        }
        
        .stat-value {
            font-size: 1.5rem;
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
        
        .transactions-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .transactions-table th {
            background-color: #f8f9fa;
            padding: 0.75rem;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
        }
        
        .transactions-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #dee2e6;
        }
        
        .transactions-table tr:last-child td {
            border-bottom: none;
        }
        
        .transactions-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .status-sold {
            color: #007bff;
        }
        
        .status-active {
            color: #28a745;
        }
        
        .status-pending {
            color: #ffc107;
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
        
        .balance-amount {
            font-size: 1.5rem;
            font-weight: bold;
            color: #007cba;
        }
        
        .pending-balance {
            font-size: 1rem;
            color: #ffc107;
        }
        
        .contact-info {
            background-color: #e9f7ef;
            padding: 1rem;
            border-radius: 4px;
            margin: 1rem 0;
        }
    </style>
</head>
<body>
    <header class="header">
        <h1><?php echo defined('SITE_NAME') ? SITE_NAME : '奇楠沉香交易拍賣平台'; ?> - 個人資料</h1>
        <div class="nav-links">
            <a href="dashboard.php">儀表板</a>
            <a href="my-items.php">我的商品</a>
            <a href="../index.php">首頁</a>
            <a href="../logout.php">登出</a>
        </div>
    </header>
    
    <div class="container">
        <div class="page-header">
            <h2>個人資料管理</h2>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div class="message error"><?php echo safeOutput($error_message); ?></div>
        <?php endif; ?>
        
        <?php if (isset($success_message)): ?>
            <div class="message success"><?php echo safeOutput($success_message); ?></div>
        <?php endif; ?>
        
        <div class="profile-grid">
            <div class="info-section">
                <h3>帳戶資訊</h3>
                
                <div class="info-item">
                    <div class="info-label">使用者名稱</div>
                    <div class="info-value"><?php echo safeOutput($user['username']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">手機號碼</div>
                    <div class="info-value"><?php echo safeOutput($user['phone']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">電子信箱</div>
                    <div class="info-value"><?php echo safeOutput($user['email']); ?></div>
                </div>
                
                <div class="contact-info">
                    <strong>聯絡資訊說明：</strong>
                    <p>手機號碼和電子信箱可用於接收系統通知和交易相關訊息。</p>
                </div>
                
                <div class="info-item">
                    <div class="info-label">目前帳戶餘額</div>
                    <div class="info-value balance-amount"><?php echo formatCurrency($user['balance']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">註冊時間</div>
                    <div class="info-value"><?php echo formatDateTime($user['created_at']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">待收取餘額</div>
                    <div class="info-value pending-balance">
                        <?php echo formatCurrency($sold_stats['total_sales'] ?? 0); ?>
                    </div>
                    <div style="font-size: 0.8rem; color: #6c757d; margin-top: 0.25rem;">
                        已結標商品的款項等待管理員確認入帳
                    </div>
                </div>
            </div>
            
            <div class="info-section">
                <h3>修改個人資訊</h3>
                
                <form method="POST" action="">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="form-group">
                        <label for="phone">手機號碼 *</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">電子信箱 *</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                    </div>
                    
                    <button type="submit" class="btn btn-success">更新個人資訊</button>
                </form>
                
                <h3 style="margin-top: 2rem;">修改密碼</h3>
                
                <form method="POST" action="" style="margin-top: 1rem;">
                    <input type="hidden" name="change_password" value="1">
                    
                    <div class="form-group">
                        <label for="current_password">目前密碼 *</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">新密碼 *（至少6個字元）</label>
                        <input type="password" id="new_password" name="new_password" minlength="6" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">確認新密碼 *</label>
                        <input type="password" id="confirm_password" name="confirm_password" minlength="6" required>
                    </div>
                    
                    <button type="submit" class="btn">更新密碼</button>
                </form>
            </div>
        </div>
        
        <div class="info-section" style="margin-top: 2rem;">
            <h3>商品統計</h3>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h4>待刊登</h4>
                    <div class="stat-value pending-stat"><?php echo $pending_count; ?></div>
                </div>
                <div class="stat-card">
                    <h4>刊登中</h4>
                    <div class="stat-value active-stat"><?php echo $active_count; ?></div>
                </div>
                <div class="stat-card">
                    <h4>已結標</h4>
                    <div class="stat-value sold-stat"><?php echo $sold_stats['sold_count'] ?? 0; ?></div>
                </div>
                <div class="stat-card">
                    <h4>總銷售額</h4>
                    <div class="stat-value"><?php echo formatCurrency($sold_stats['total_sales'] ?? 0); ?></div>
                </div>
            </div>
        </div>
        
        <div class="info-section" style="margin-top: 2rem;">
            <h3>最近交易記錄</h3>
            
            <?php if ($recent_transactions && $recent_transactions->num_rows > 0): ?>
                <table class="transactions-table">
                    <thead>
                        <tr>
                            <th>商品名稱</th>
                            <th>狀態</th>
                            <th>金額</th>
                            <th>時間</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($transaction = $recent_transactions->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo safeOutput($transaction['item_name']); ?></td>
                                <td>
                                    <?php 
                                    switch ($transaction['status']) {
                                        case 'pending': 
                                            echo '<span class="status-pending">待刊登</span>'; 
                                            break;
                                        case 'active': 
                                            echo '<span class="status-active">刊登中</span>'; 
                                            break;
                                        case 'sold': 
                                            echo '<span class="status-sold">已結標</span>'; 
                                            break;
                                        default: 
                                            echo $transaction['status'];
                                    }
                                    ?>
                                </td>
                                <td><?php echo $transaction['final_price'] ? formatCurrency($transaction['final_price']) : 'N/A'; ?></td>
                                <td><?php echo formatDateTime($transaction['transaction_time']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; color: #6c757d; font-style: italic; padding: 1rem;">
                    目前沒有交易記錄
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
