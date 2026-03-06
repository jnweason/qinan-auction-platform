<?php
/**
 * 管理員登入頁面
 */

// 啟動 Session
session_start();

// 引入必要的檔案
include "../includes/db_connect.php";
include "../includes/functions.php";

// 如果已經登入，重新導向到適當的頁面
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: dashboard.php");
    } else {
        header("Location: ../customer/dashboard.php");
    }
    exit();
}

// 初始化變數
$error_message = '';

// 處理登入表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 取得表單資料
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // 驗證輸入資料
    if (empty($username) || empty($password)) {
        $error_message = '請填寫所有欄位';
    } else {
        try {
            // 查詢使用者資料（限定為管理員角色）
            $sql = "SELECT id, username, password, role FROM users WHERE username = ? AND role = 'admin'";
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
                    
                    // 重新導向到管理員儀表板
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error_message = '密碼錯誤';
                }
            } else {
                $error_message = '管理員帳號不存在';
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
    <title>管理員登入 - <?php echo defined('SITE_NAME') ? SITE_NAME : '奇楠沉香交易拍賣平台'; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .login-container {
            background-color: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            width: 400px;
            max-width: 90%;
        }
        
        .login-container h2 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 24px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: bold;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e1e1;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            border-color: #007cba;
            outline: none;
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #007cba 0%, #0056b3 100%);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: linear-gradient(135deg, #0056b3 0%, #003d7a 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,91,179,0.3);
        }
        
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .logo h1 {
            color: #333;
            margin: 0;
            font-size: 28px;
        }
        
        .logo p {
            color: #666;
            margin: 5px 0 0 0;
            font-size: 14px;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #007cba;
            text-decoration: none;
            font-size: 14px;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        .admin-badge {
            background-color: #007cba;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            display: inline-block;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <div class="admin-badge">管理員專用</div>
            <h1><?php echo defined('SITE_NAME') ? SITE_NAME : '奇楠沉香交易拍賣平台'; ?></h1>
            <p>管理後台登入</p>
        </div>
        
        <h2>管理員登入</h2>
        
        <?php if ($error_message): ?>
            <div class="message error"><?php echo safeOutput($error_message); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">管理員帳號：</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">密碼：</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn">登入管理後台</button>
        </form>
        
        <div class="back-link">
            <a href="../index.php">&larr; 返回首頁</a>
        </div>
    </div>
</body>
</html>
