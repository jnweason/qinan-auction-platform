<?php
/**
 * 客戶新增商品頁面
 * 客戶可以自行新增拍賣商品
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

// 初始化變數
$error_message = '';
$success_message = '';

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 取得表單資料
    $name = trim($_POST['name'] ?? '');
    $origin = trim($_POST['origin'] ?? '');
    $weight = floatval($_POST['weight'] ?? 0);
    $start_price = floatval($_POST['start_price'] ?? 0);
    $reserve_price = floatval($_POST['reserve_price'] ?? 0);
    $remarks = trim($_POST['remarks'] ?? '');
    
    // 驗證輸入資料
    if (empty($name)) {
        $error_message = '請輸入商品名稱';
    } elseif (empty($origin)) {
        $error_message = '請輸入產地';
    } elseif ($weight <= 0) {
        $error_message = '請輸入正確的重量';
    } elseif ($start_price < 0) {
        $error_message = '開價不能為負數';
    } elseif ($reserve_price < 0) {
        $error_message = '預期結標金額不能為負數';
    } elseif ($reserve_price < $start_price) {
        $error_message = '預期結標金額不能低於開價';
    } else {
        try {
            // 插入商品資料（狀態預設為待刊登）
            $insert_sql = "INSERT INTO items (seller_id, name, origin, weight, start_price, reserve_price, remarks, status) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("issddds", $current_user_id, $name, $origin, $weight, $start_price, $reserve_price, $remarks);
            
            if ($stmt->execute()) {
                $item_id = $conn->insert_id;
                $success_message = '商品新增成功！';
                
                // 清空表單資料
                $name = '';
                $origin = '';
                $weight = '';
                $start_price = '';
                $reserve_price = '';
                $remarks = '';
                
                // 提供圖片上傳連結
                $upload_link = "<a href='upload-images.php?item_id={$item_id}'>點此上傳圖片</a>";
                $success_message .= " {$upload_link}";
            } else {
                $error_message = '新增商品失敗，請稍後再試';
            }
        } catch (Exception $e) {
            $error_message = '新增商品時發生錯誤：' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新增商品 - <?php echo SITE_NAME; ?></title>
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
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ced4da;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 1rem;
        }
        
        .form-group textarea {
            height: 100px;
            resize: vertical;
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
        <h1><?php echo SITE_NAME; ?> - 新增商品</h1>
        <div class="nav-links">
            <a href="dashboard.php">儀表板</a>
            <a href="my-items.php">我的商品</a>
            <a href="../index.php">首頁</a>
            <a href="profile.php">個人資料</a>
            <a href="../logout.php">登出</a>
        </div>
    </header>
    
    <div class="container">
        <a href="my-items.php" class="back-link">&larr; 返回我的商品</a>
        
        <div class="page-header">
            <h2>新增商品</h2>
        </div>
        
        <?php if ($error_message): ?>
            <div class="message error"><?php echo safeOutput($error_message); ?></div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="name">商品名稱 <span class="required">*</span></label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="origin">產地 <span class="required">*</span></label>
                    <input type="text" id="origin" name="origin" value="<?php echo htmlspecialchars($origin ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="weight">重量 (克) <span class="required">*</span></label>
                    <input type="number" id="weight" name="weight" step="0.01" min="0.01" value="<?php echo htmlspecialchars($weight ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="start_price">開價 <span class="required">*</span></label>
                    <input type="number" id="start_price" name="start_price" step="0.01" min="0" value="<?php echo htmlspecialchars($start_price ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="reserve_price">預期結標金額 <span class="required">*</span></label>
                    <input type="number" id="reserve_price" name="reserve_price" step="0.01" min="0" value="<?php echo htmlspecialchars($reserve_price ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="remarks">其他備註</label>
                    <textarea id="remarks" name="remarks"><?php echo htmlspecialchars($remarks ?? ''); ?></textarea>
                </div>
                
                <button type="submit" class="btn">新增商品</button>
            </form>
        </div>
    </div>
</body>
</html>
