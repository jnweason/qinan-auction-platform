<?php
/**
 * 系統安裝腳本
 * 用於初始化資料庫和建立預設帳號
 */

// 設定錯誤報告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 檢查是否已經安裝過
$installed = false;
if (file_exists('config.php')) {
    include 'config.php';
    if (defined('DB_HOST')) {
        $installed = true;
    }
}

$message = '';
$success = false;

// 處理安裝表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$installed) {
    $db_host = $_POST['db_host'] ?? '';
    $db_user = $_POST['db_user'] ?? '';
    $db_pass = $_POST['db_pass'] ?? '';
    $db_name = $_POST['db_name'] ?? '';
    $admin_username = $_POST['admin_username'] ?? '';
    $admin_password = $_POST['admin_password'] ?? '';
    
    if (empty($db_host) || empty($db_user) || empty($db_name) || 
        empty($admin_username) || empty($admin_password)) {
        $message = '請填寫所有必填欄位';
    } elseif (strlen($admin_password) < 6) {
        $message = '管理員密碼至少需要6個字元';
    } else {
        try {
            // 建立資料庫連線
            $conn = new mysqli($db_host, $db_user, $db_pass);
            
            if ($conn->connect_error) {
                throw new Exception("資料庫連接失敗: " . $conn->connect_error);
            }
            
            // 建立資料庫（如果不存在）
            $conn->query("CREATE DATABASE IF NOT EXISTS `$db_name`");
            $conn->select_db($db_name);
            
            // 建立資料表
            $sql_tables = "
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE,
                password VARCHAR(255),
                role ENUM('admin', 'customer'),
                balance DECIMAL(10,2) DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            
            CREATE TABLE IF NOT EXISTS items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                seller_id INT,
                name VARCHAR(100),
                origin VARCHAR(100),
                weight FLOAT,
                start_price DECIMAL(10,2),
                reserve_price DECIMAL(10,2),
                current_bid DECIMAL(10,2) DEFAULT 0,
                final_price DECIMAL(10,2) DEFAULT NULL,
                status ENUM('pending','active','sold','unsold') DEFAULT 'pending',
                remarks TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (seller_id) REFERENCES users(id)
            );
            
            CREATE TABLE IF NOT EXISTS bids (
                id INT AUTO_INCREMENT PRIMARY KEY,
                item_id INT,
                bidder_id INT,
                amount DECIMAL(10,2),
                bid_time DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (item_id) REFERENCES items(id),
                FOREIGN KEY (bidder_id) REFERENCES users(id)
            );
            
            CREATE TABLE IF NOT EXISTS images (
                id INT AUTO_INCREMENT PRIMARY KEY,
                item_id INT,
                file_path VARCHAR(255),
                FOREIGN KEY (item_id) REFERENCES items(id)
            );
            ";
            
            if (!$conn->multi_query($sql_tables)) {
                throw new Exception("建立資料表失敗: " . $conn->error);
            }
            
            // 等待所有查詢完成
            do {
                if ($result = $conn->store_result()) {
                    $result->free();
                }
            } while ($conn->more_results() && $conn->next_result());
            
            // 建立管理員帳號
            $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
            $insert_admin = "INSERT IGNORE INTO users (username, password, role) VALUES (?, ?, 'admin')";
            $stmt = $conn->prepare($insert_admin);
            $stmt->bind_param("ss", $admin_username, $hashed_password);
            $stmt->execute();
            
            // 建立 config.php 檔案
            $config_content = "<?php
/**
 * 系統設定檔
 */
define('DB_HOST', '$db_host');
define('DB_USER', '$db_user');
define('DB_PASS', '$db_pass');
define('DB_NAME', '$db_name');

define('UPLOAD_DIR', __DIR__ . '/assets/uploads/');
define('ADMIN_UPLOAD_DIR', UPLOAD_DIR . 'admin-uploads/');
define('USER_UPLOAD_DIR', UPLOAD_DIR . 'user-uploads/');

define('SITE_NAME', '奇楠沉香交易拍賣平台');

define('PAGE_SIZES', [10, 20, 30]);
?>
";
            
            file_put_contents('config.php', $config_content);
            
            // 建立上傳目錄
            if (!is_dir('assets/uploads')) {
                mkdir('assets/uploads', 0755, true);
            }
            if (!is_dir('assets/uploads/admin-uploads')) {
                mkdir('assets/uploads/admin-uploads', 0755, true);
            }
            if (!is_dir('assets/uploads/user-uploads')) {
                mkdir('assets/uploads/user-uploads', 0755, true);
            }
            
            $success = true;
            $message = '系統安裝成功！請刪除 install.php 檔案以確保安全。';
            
        } catch (Exception $e) {
            $message = '安裝失敗: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系統安裝 - 奇楠沉香交易拍賣平台</title>
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
        
        .install-container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            width: 500px;
            max-width: 90%;
        }
        
        .install-container h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 16px;
        }
        
        .btn {
            width: 100%;
            padding: 15px;
            background-color: #007cba;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 18px;
            font-weight: bold;
        }
        
        .btn:hover {
            background-color: #0056b3;
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
        
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .section h2 {
            color: #333;
            margin-top: 0;
            margin-bottom: 15px;
        }
        
        .note {
            background-color: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #ffeaa7;
            margin-top: 20px;
        }
        
        .warning {
            color: #dc3545;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <h1>奇楠沉香交易拍賣平台安裝</h1>
        
        <?php if ($installed): ?>
            <div class="message success">
                系統已經安裝過了！<br>
                請刪除 install.php 檔案以確保安全。
            </div>
        <?php elseif ($success): ?>
            <div class="message success">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php else: ?>
            <?php if ($message): ?>
                <div class="message error">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="section">
                    <h2>資料庫設定</h2>
                    
                    <div class="form-group">
                        <label for="db_host">資料庫主機 *</label>
                        <input type="text" id="db_host" name="db_host" value="localhost" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_user">資料庫使用者名稱 *</label>
                        <input type="text" id="db_user" name="db_user" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_pass">資料庫密碼</label>
                        <input type="password" id="db_pass" name="db_pass">
                    </div>
                    
                    <div class="form-group">
                        <label for="db_name">資料庫名稱 *</label>
                        <input type="text" id="db_name" name="db_name" value="qinan_auction" required>
                    </div>
                </div>
                
                <div class="section">
                    <h2>管理員帳號設定</h2>
                    
                    <div class="form-group">
                        <label for="admin_username">管理員使用者名稱 *</label>
                        <input type="text" id="admin_username" name="admin_username" value="admin" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_password">管理員密碼 *（至少6個字元）</label>
                        <input type="password" id="admin_password" name="admin_password" minlength="6" required>
                    </div>
                </div>
                
                <button type="submit" class="btn">開始安裝</button>
            </form>
            
            <div class="note">
                <span class="warning">重要提醒：</span><br>
                安裝完成後，請務必刪除 install.php 檔案以確保系統安全。<br>
                建議設定適當的檔案權限以保護系統安全。
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
