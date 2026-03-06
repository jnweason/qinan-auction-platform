<?php
/**
 * 註冊頁面（修正版）
 * 增加手機號碼和電子信箱欄位
 */

// 啟動 Session
session_start();

// 錯誤報告（開發時使用，上線後請移除）
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// 初始化變數
$error_message = '';
$success_message = '';
$username = '';
$phone = '';
$email = '';

try {
    // 引入必要的檔案
    if (file_exists("includes/db_connect.php")) {
        include "includes/db_connect.php";
    } else {
        die("系統錯誤：無法載入必要的檔案");
    }
    
    if (file_exists("includes/functions.php")) {
        include "includes/functions.php";
    }
    
    // 如果已經登入，重新導向
    if (isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit();
    }
    
    // 處理註冊表單提交
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // 取得表單資料
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
        $role = 'customer'; // 預設為客戶角色
        
        // 驗證輸入資料
        if (empty($username) || empty($phone) || empty($email) || empty($password) || empty($confirm_password)) {
            $error_message = '請填寫所有欄位';
        } elseif (strlen($username) < 3) {
            $error_message = '使用者名稱至少需要3個字元';
        } elseif (strlen($password) < 6) {
            $error_message = '密碼至少需要6個字元';
        } elseif ($password !== $confirm_password) {
            $error_message = '密碼確認不一致';
        } elseif (!isValidEmail($email)) { // 使用 functions.php 中的函數
            $error_message = '請輸入正確的電子信箱格式';
        } elseif (!preg_match('/^09\d{8}$/', $phone)) {
            $error_message = '請輸入正確的手機號碼格式（09開頭共10碼）';
        } else {
            // 確保資料庫連線存在
            if (!isset($conn)) {
                $error_message = '資料庫連線失敗';
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
                        // 檢查手機號碼是否已存在
                        $check_phone_sql = "SELECT id FROM users WHERE phone = ?";
                        $stmt = $conn->prepare($check_phone_sql);
                        $stmt->bind_param("s", $phone);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            $error_message = '該手機號碼已被使用';
                        } else {
                            // 檢查電子信箱是否已存在
                            $check_email_sql = "SELECT id FROM users WHERE email = ?";
                            $stmt = $conn->prepare($check_email_sql);
                            $stmt->bind_param("s", $email);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            if ($result->num_rows > 0) {
                                $error_message = '該電子信箱已被使用';
                            } else {
                                // 加密密碼
                                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                                
                                // 插入新使用者
                                $insert_sql = "INSERT INTO users (username, phone, email, password, role) VALUES (?, ?, ?, ?, ?)";
                                $stmt = $conn->prepare($insert_sql);
                                $stmt->bind_param("sssss", $username, $phone, $email, $hashed_password, $role);
                                
                                if ($stmt->execute()) {
                                    $success_message = '註冊成功！請登入您的帳號。';
                                    // 清空表單資料
                                    $username = '';
                                    $phone = '';
                                    $email = '';
                                } else {
                                    $error_message = '註冊失敗，請稍後再試';
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    $error_message = '註冊時發生錯誤，請稍後再試';
                }
            }
        }
    }
} catch (Exception $e) {
    $error_message = '系統錯誤，請聯繫管理員';
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>註冊 - 奇楠沉香交易拍賣平台</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .register-container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 350px;
            max-width: 90%;
        }
        
        .register-container h2 {
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
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .btn:hover {
            background-color: #45a049;
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
        
        .required {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>奇楠沉香交易拍賣平台 註冊</h2>
        
        <?php if (!empty($error_message)): ?>
            <div class="message error"><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="message success"><?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">使用者名稱：<span class="required">*</span></label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="phone">手機號碼：<span class="required">*</span></label>
                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="0912345678" required>
            </div>
            
            <div class="form-group">
                <label for="email">電子信箱：<span class="required">*</span></label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="example@email.com" required>
            </div>
            
            <div class="form-group">
                <label for="password">密碼：<span class="required">*</span></label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">確認密碼：<span class="required">*</span></label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit" class="btn">註冊</button>
        </form>
        
        <div class="links">
            <p>已經有帳號？<a href="login.php">立即登入</a></p>
        </div>
    </div>
</body>
</html>
