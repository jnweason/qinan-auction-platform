<?php
/**
 * 編輯商品頁面
 * 管理員可以編輯商品資訊和狀態
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

// 取得商品 ID
$item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 檢查商品 ID 是否有效
if ($item_id <= 0) {
    header("Location: manage-items.php");
    exit();
}

// 查詢商品資訊
$sql = "SELECT i.*, u.username as seller_name FROM items i LEFT JOIN users u ON i.seller_id = u.id WHERE i.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();

// 檢查商品是否存在
if ($result->num_rows == 0) {
    header("Location: manage-items.php");
    exit();
}

$item = $result->fetch_assoc();

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
    $status = $_POST['status'] ?? $item['status'];
    
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
            // 更新商品資料
            $update_sql = "UPDATE items SET name = ?, origin = ?, weight = ?, start_price = ?, reserve_price = ?, remarks = ?, status = ? WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ssddsssi", $name, $origin, $weight, $start_price, $reserve_price, $remarks, $status, $item_id);
            
            if ($stmt->execute()) {
                $success_message = '商品更新成功！';
                
                // 重新載入商品資訊
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $item_id);
                $stmt->execute();
                $item = $stmt->get_result()->fetch_assoc();
            } else {
                $error_message = '更新商品失敗，請稍後再試';
            }
        } catch (Exception $e) {
            $error_message = '更新商品時發生錯誤：' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>編輯商品 - <?php echo SITE_NAME; ?></title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
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
        .form-group select,
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
        
        .warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .required {
            color: #dc3545;
        }
        
        .status-info {
            background-color: #d4edda;
            color: #155724;
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
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <h1><?php echo SITE_NAME; ?> - 編輯商品</h1>
        <div class="nav-links">
            <a href="dashboard.php">儀表板</a>
            <a href="manage-items.php">商品管理</a>
            <a href="../index.php">返回首頁</a>
            <a href="../logout.php">登出</a>
        </div>
    </header>
    
    <div class="container">
        <a href="manage-items.php" class="back-link">&larr; 返回商品管理</a>
        
        <div class="page-header">
            <h2>編輯商品</h2>
        </div>
        
        <?php if ($error_message): ?>
            <div class="message error"><?php echo safeOutput($error_message); ?></div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="message success"><?php echo safeOutput($success_message); ?></div>
        <?php endif; ?>
        
        <div class="status-info">
            商品目前狀態：<?php 
            switch ($item['status']) {
                case 'pending': echo '待刊登'; break;
                case 'active': echo '刊登中'; break;
                case 'sold': echo '已結標'; break;
                case 'unsold': echo '已流標'; break;
                default: echo '未知';
            }
            ?>
            <br><small>管理員可以隨時更改商品狀態</small>
        </div>
        
        <div class="form-container">
            <form method="POST" action="">
                <div class="form-group">
                    <label>商品編號</label>
                    <input type="text" value="<?php echo $item['id']; ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label>賣家</label>
                    <input type="text" value="<?php echo safeOutput($item['seller_name']); ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label for="name">商品名稱 <span class="required">*</span></label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           value="<?php echo htmlspecialchars($item['name'] ?? ''); ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="origin">產地 <span class="required">*</span></label>
                    <input type="text" 
                           id="origin" 
                           name="origin" 
                           value="<?php echo htmlspecialchars($item['origin'] ?? ''); ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="weight">重量 (克) <span class="required">*</span></label>
                    <input type="number" 
                           id="weight" 
                           name="weight" 
                           step="0.01" 
                           min="0.01" 
                           value="<?php echo htmlspecialchars($item['weight'] ?? ''); ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="start_price">開價 <span class="required">*</span></label>
                    <input type="number" 
                           id="start_price" 
                           name="start_price" 
                           step="0.01" 
                           min="0" 
                           value="<?php echo htmlspecialchars($item['start_price'] ?? ''); ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="reserve_price">預期結標金額 <span class="required">*</span></label>
                    <input type="number" 
                           id="reserve_price" 
                           name="reserve_price" 
                           step="0.01" 
                           min="0" 
                           value="<?php echo htmlspecialchars($item['reserve_price'] ?? ''); ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="remarks">其他備註</label>
                    <textarea id="remarks" 
                              name="remarks"><?php echo htmlspecialchars($item['remarks'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="status">商品狀態</label>
                    <select id="status" name="status">
                        <option value="pending" <?php echo $item['status'] === 'pending' ? 'selected' : ''; ?>>待刊登</option>
                        <option value="active" <?php echo $item['status'] === 'active' ? 'selected' : ''; ?>>刊登中</option>
                        <option value="sold" <?php echo $item['status'] === 'sold' ? 'selected' : ''; ?>>已結標</option>
                        <option value="unsold" <?php echo $item['status'] === 'unsold' ? 'selected' : ''; ?>>已流標</option>
                    </select>
                    <div style="font-size: 0.9rem; color: #6c757d; margin-top: 0.5rem;">
                        管理員可以隨時更改商品狀態
                    </div>
                </div>
                
                <button type="submit" class="btn">更新商品</button>
            </form>
        </div>
    </div>
</body>
</html>
