<?php
/**
 * 編輯用戶頁面
 * 管理員可以編輯客戶帳號資訊
 */

// 啟用錯誤報告以幫助除錯
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// 取得用戶 ID
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 檢查用戶 ID 是否有效
if ($user_id <= 0) {
    header("Location: manage-users.php");
    exit();
}

// 查詢用戶資訊
$sql = "SELECT * FROM users WHERE id = ? AND role = 'customer'";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL 準備失敗: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
if (!$stmt->execute()) {
    die("查詢執行失敗: " . $stmt->error);
}
$result = $stmt->get_result();

// 檢查用戶是否存在
if ($result->num_rows == 0) {
    header("Location: manage-users.php");
    exit();
}

$user = $result->fetch_assoc();

// 初始化變數
$error_message = '';
$success_message = '';

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 取得表單資料
    $username = trim($_POST['username'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $balance = floatval($_POST['balance'] ?? 0);
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    
    // 驗證輸入資料
    if (empty($username)) {
        $error_message = '請輸入使用者名稱';
    } elseif (empty($phone)) {
        $error_message = '請輸入手機號碼';
    } elseif (empty($email)) {
        $error_message = '請輸入電子信箱';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = '請輸入正確的電子信箱格式';
    } elseif (!preg_match('/^09\d{8}$/', $phone)) {
        $error_message = '請輸入正確的手機號碼格式（09開頭共10碼）';
    } elseif ($balance < 0) {
        $error_message = '帳戶餘額不能為負數';
    } elseif (!empty($new_password) && strlen($new_password) < 6) {
        $error_message = '新密碼至少需要6個字元';
    } elseif (!empty($new_password) && $new_password !== $confirm_password) {
        $error_message = '密碼確認不一致';
    } else {
        try {
            // 檢查使用者名稱是否已被其他用戶使用
            $check_sql = "SELECT id FROM users WHERE username = ? AND id != ?";
            $stmt = $conn->prepare($check_sql);
            if (!$stmt) {
                throw new Exception("SQL 準備失敗: " . $conn->error);
            }
            $stmt->bind_param("si", $username, $user_id);
            if (!$stmt->execute()) {
                throw new Exception("查詢執行失敗: " . $stmt->error);
            }
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error_message = '該使用者名稱已被其他用戶使用';
            } else {
                // 檢查手機號碼是否已被其他用戶使用
                $check_phone_sql = "SELECT id FROM users WHERE phone = ? AND id != ?";
                $stmt = $conn->prepare($check_phone_sql);
                if (!$stmt) {
                    throw new Exception("SQL 準備失敗: " . $conn->error);
                }
                $stmt->bind_param("si", $phone, $user_id);
                if (!$stmt->execute()) {
                    throw new Exception("查詢執行失敗: " . $stmt->error);
                }
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $error_message = '該手機號碼已被其他用戶使用';
                } else {
                    // 檢查電子信箱是否已被其他用戶使用
                    $check_email_sql = "SELECT id FROM users WHERE email = ? AND id != ?";
                    $stmt = $conn->prepare($check_email_sql);
                    if (!$stmt) {
                        throw new Exception("SQL 準備失敗: " . $conn->error);
                    }
                    $stmt->bind_param("si", $email, $user_id);
                    if (!$stmt->execute()) {
                        throw new Exception("查詢執行失敗: " . $stmt->error);
                    }
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $error_message = '該電子信箱已被其他用戶使用';
                    } else {
                        // 更新用戶資訊
                        if (!empty($new_password)) {
                            // 有新密碼，更新密碼
                            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                            $update_sql = "UPDATE users SET username = ?, phone = ?, email = ?, balance = ?, password = ? WHERE id = ?";
                            $stmt = $conn->prepare($update_sql);
                            if (!$stmt) {
                                throw new Exception("SQL 準備失敗: " . $conn->error);
                            }
                            // 正確的綁定參數順序：username(string), phone(string), email(string), balance(decimal), password(string), id(integer)
                            $stmt->bind_param("sssdsi", $username, $phone, $email, $balance, $hashed_password, $user_id);
                        } else {
                            // 沒有新密碼，只更新其他資訊
                            $update_sql = "UPDATE users SET username = ?, phone = ?, email = ?, balance = ? WHERE id = ?";
                            $stmt = $conn->prepare($update_sql);
                            if (!$stmt) {
                                throw new Exception("SQL 準備失敗: " . $conn->error);
                            }
                            // 正確的綁定參數順序：username(string), phone(string), email(string), balance(decimal), id(integer)
                            $stmt->bind_param("ssssi", $username, $phone, $email, $balance, $user_id);
                        }
                        
                        if ($stmt->execute()) {
                            $success_message = '用戶資訊更新成功！';
                            
                            // 重新載入用戶資訊
                            $stmt = $conn->prepare($sql);
                            if (!$stmt) {
                                throw new Exception("SQL 準備失敗: " . $conn->error);
                            }
                            $stmt->bind_param("i", $user_id);
                            if (!$stmt->execute()) {
                                throw new Exception("查詢執行失敗: " . $stmt->error);
                            }
                            $user = $stmt->get_result()->fetch_assoc();
                        } else {
                            $error_message = '更新用戶資訊失敗，請稍後再試';
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log("更新用戶資訊時發生錯誤：" . $e->getMessage());
            $error_message = '更新用戶資訊時發生錯誤，請稍後再試';
        }
    }
}

// 安全的日期格式化函數
function safeFormatDateTime($datetime) {
    if (empty($datetime)) {
        return '尚未註冊';
    }
    try {
        return date('Y-m-d H:i:s', strtotime($datetime));
    } catch (Exception $e) {
        return '日期格式錯誤';
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>編輯用戶 - <?php echo defined('SITE_NAME') ? SITE_NAME : '奇楠沉香交易拍賣平台'; ?></title>
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
            max-width: 800px;
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
        
        .back-link {
            display: inline-block;
            margin-bottom: 1rem;
            color: #007cba;
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .form-container {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
        
        .required {
            color: #dc3545;
        }
        
        .password-note {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 0.5rem;
        }
        
        .user-info {
            background-color: #e9f7ef;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            border: 1px solid #c3e6cb;
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 1rem;
            }
            
            .form-container {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <h1><?php echo defined('SITE_NAME') ? SITE_NAME : '奇楠沉香交易拍賣平台'; ?> - 編輯用戶</h1>
        <div class="nav-links">
            <a href="dashboard.php">儀表板</a>
            <a href="manage-users.php">用戶管理</a>
            <a href="../index.php">返回首頁</a>
            <a href="../logout.php">登出</a>
        </div>
    </header>
    
    <div class="container">
        <a href="manage-users.php" class="back-link">&larr; 返回用戶管理</a>
        
        <div class="page-header">
            <h2>編輯用戶</h2>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <div class="user-info">
            <strong>用戶基本資訊：</strong><br>
            註冊時間：<?php echo safeFormatDateTime($user['created_at']); ?>
        </div>
        
        <div class="form-container">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">使用者名稱 <span class="required">*</span></label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">手機號碼 <span class="required">*</span></label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="0912345678" required>
                </div>
                
                <div class="form-group">
                    <label for="email">電子信箱 <span class="required">*</span></label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" placeholder="example@email.com" required>
                </div>
                
                <div class="form-group">
                    <label for="balance">帳戶餘額</label>
                    <input type="number" id="balance" name="balance" step="0.01" min="0" value="<?php echo htmlspecialchars($user['balance'] ?? '0'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="new_password">新密碼（留空則不更改）</label>
                    <input type="password" id="new_password" name="new_password">
                    <div class="password-note">密碼至少需要6個字元</div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">確認新密碼</label>
                    <input type="password" id="confirm_password" name="confirm_password">
                </div>
                
                <button type="submit" class="btn">更新用戶資訊</button>
            </form>
        </div>
    </div>
</body>
</html>
