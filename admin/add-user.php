<?php
/**
 * 新增用戶頁面
 * 管理員可以新增客戶帳號
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

// 初始化變數
$error_message = '';
$success_message = '';

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 取得表單資料
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $balance = floatval($_POST['balance'] ?? 0);
    
    // 驗證輸入資料
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $error_message = '請填寫所有必填欄位';
    } elseif (strlen($username) < 3) {
        $error_message = '使用者名稱至少需要3個字元';
    } elseif (strlen($password) < 6) {
        $error_message = '密碼至少需要6個字元';
    } elseif ($password !== $confirm_password) {
        $error_message = '密碼確認不一致';
    } elseif ($balance < 0) {
        $error_message = '帳戶餘額不能為負數';
    } else {
        try {
            // 檢查使用者名稱是否已存在
            $check_sql = "SELECT id FROM users WHERE username = ?";
            $stmt = $conn->prepare($check_sql);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error_message = '該使用者名稱已被使用';
            } else {
                // 加密密碼
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // 插入新用戶（角色固定為客戶）
                $insert_sql = "INSERT INTO users (username, password, role, balance) VALUES (?, ?, 'customer', ?)";
                $stmt = $conn->prepare($insert_sql);
                $stmt->bind_param("ssd", $username, $hashed_password, $balance);
                
                if ($stmt->execute()) {
                    $success_message = '用戶新增成功！';
                    // 清空表單資料
                    $username = '';
                    $balance = 0;
                } else {
                    $error_message = '新增用戶失敗，請稍後再試';
                }
            }
        } catch (Exception $e) {
            $error_message = '新增用戶時發生錯誤：' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新增用戶 - <?php echo SITE_NAME; ?></title>
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
        <h1><?php echo SITE_NAME; ?> - 新增用戶</h1>
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
            <h2>新增用戶</h2>
        </div>
        
        <?php if ($error_message): ?>
            <div class="message error"><?php echo safeOutput($error_message); ?></div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="message success"><?php echo safeOutput($success_message); ?></div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">使用者名稱 <span class="required">*</span></label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">密碼 <span class="required">*</span></label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">確認密碼 <span class="required">*</span></label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <div class="form-group">
                    <label for="balance">初始帳戶餘額</label>
                    <input type="number" id="balance" name="balance" step="0.01" min="0" value="<?php echo htmlspecialchars($balance ?? '0'); ?>">
                </div>
                
                <button type="submit" class="btn">新增用戶</button>
            </form>
        </div>
    </div>
</body>
</html>
