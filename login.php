<?php
/**
 * 登入頁面
 * 處理使用者登入功能
 */

// 啟動 Session
session_start();

// 引入必要的檔案
include "includes/db_connect.php";
include "includes/functions.php";

// 初始化變數
$error_message = '';
$success_message = '';

// 如果已經登入，重新導向到適當的頁面
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: customer/dashboard.php");
    }
    exit();
}

// 處理登入表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 取得表單資料並進行安全處理
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // 驗證輸入資料
    if (empty($username) || empty($password)) {
        $error_message = '請填寫所有欄位';
    } else {
        try {
            // 查詢使用者資料
            $sql = "SELECT id, username, password, role, balance FROM users WHERE username = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($user = $result->fetch_assoc()) {
                // 驗證密碼
                if (password_verify($password, $user['password'])) {
                    // 登入成功，設定 Session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['balance'] = $user['balance'];
                    
                    // 重新導向到適當的頁面
                    if ($user['role'] === 'admin') {
                        header("Location: admin/dashboard.php");
                    } else {
                        header("Location: customer/dashboard.php");
                    }
                    exit();
                } else {
                    $error_message = '密碼錯誤';
                }
            } else {
                $error_message = '使用者不存在';
            }
        } catch (Exception $e) {
            $error_message = '登入時發生錯誤，請稍後再試';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登入 - <?php echo SITE_NAME; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        
        .login-container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 350px;
        }
        
        .login-container h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background-color: #007cba;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .btn:hover {
            background-color: #005a87;
        }
        
        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
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
        
        .links {
            text-align: center;
            margin-top: 20px;
        }
        
        .links a {
            color: #007cba;
            text-decoration: none;
        }
        
        .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2><?php echo SITE_NAME; ?> 登入</h2>
        
        <?php if ($error_message): ?>
            <div class="message error"><?php echo safeOutput($error_message); ?></div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="message success"><?php echo safeOutput($success_message); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">使用者名稱：</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">密碼：</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn">登入</button>
        </form>
        
        <div class="links">
            <p>還沒有帳號？<a href="register.php">立即註冊</a></p>
        </div>
    </div>
</body>
</html>
