<?php
/**
 * 客戶編輯商品頁面
 * 客戶可以查看所有狀態的商品，但只能編輯待刊登的商品
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

// 取得商品 ID
$item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 檢查商品 ID 是否有效
if ($item_id <= 0) {
    header("Location: my-items.php");
    exit();
}

// 查詢商品資訊（必須是當前用戶的商品）
$sql = "SELECT * FROM items WHERE id = ? AND seller_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $item_id, $current_user_id);
$stmt->execute();
$result = $stmt->get_result();

// 檢查商品是否存在
if ($result->num_rows == 0) {
    header("Location: my-items.php");
    exit();
}

$item = $result->fetch_assoc();

// 檢查商品是否可以編輯（只有待刊登狀態可以編輯）
$can_edit = ($item['status'] === 'pending');

// 初始化變數
$error_message = '';
$success_message = '';

// 處理表單提交（只有待刊登的商品才能修改）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_edit) {
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
            // 更新商品資料
            $update_sql = "UPDATE items SET name = ?, origin = ?, weight = ?, start_price = ?, reserve_price = ?, remarks = ? WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ssdddsi", $name, $origin, $weight, $start_price, $reserve_price, $remarks, $item_id);
            
            if ($stmt->execute()) {
                $success_message = '商品更新成功！';
                
                // 重新載入商品資訊
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $item_id, $current_user_id);
                $stmt->execute();
                $item = $stmt->get_result()->fetch_assoc();
                $can_edit = ($item['status'] === 'pending');
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
        
        .form-group input[readonly],
        .form-group select:disabled,
        .form-group textarea[readonly] {
            background-color: #f8f9fa;
            color: #6c757d;
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
        
        .required {
            color: #dc3545;
        }
        
        .note {
            background-color: #fff3cd;
            color: #856404;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            border: 1px solid #ffeaa7;
        }
        
        .status-info {
            background-color: #e9f7ef;
            color: #155724;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            border: 1px solid #c3e6cb;
        }
        
        .status-warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            border: 1px solid #ffeaa7;
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
        <h1><?php echo SITE_NAME; ?> - 編輯商品</h1>
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
            <h2>編輯商品</h2>
        </div>
        
        <?php if ($item['status'] === 'pending'): ?>
            <div class="status-info">
                商品狀態：待刊登 - 您可以編輯商品資訊
            </div>
        <?php elseif ($item['status'] === 'active'): ?>
            <div class="status-warning">
                商品狀態：刊登中 - 無法修改商品資訊
            </div>
        <?php elseif ($item['status'] === 'sold'): ?>
            <div class="status-warning">
                商品狀態：已結標 - 無法修改商品資訊
            </div>
        <?php elseif ($item['status'] === 'unsold'): ?>
            <div class="status-warning">
                商品狀態：已流標 - 無法修改商品資訊
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="message error"><?php echo safeOutput($error_message); ?></div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="message success"><?php echo safeOutput($success_message); ?></div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="name">商品名稱 <span class="required">*</span></label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($item['name'] ?? ''); ?>" <?php echo $can_edit ? 'required' : 'readonly'; ?>>
                </div>
                
                <div class="form-group">
                    <label for="origin">產地 <span class="required">*</span></label>
                    <input type="text" id="origin" name="origin" value="<?php echo htmlspecialchars($item['origin'] ?? ''); ?>" <?php echo $can_edit ? 'required' : 'readonly'; ?>>
                </div>
                
                <div class="form-group">
                    <label for="weight">重量 (克) <span class="required">*</span></label>
                    <input type="number" id="weight" name="weight" step="0.01" min="0.01" value="<?php echo htmlspecialchars($item['weight'] ?? ''); ?>" <?php echo $can_edit ? 'required' : 'readonly'; ?>>
                </div>
                
                <div class="form-group">
                    <label for="start_price">開價 <span class="required">*</span></label>
                    <input type="number" id="start_price" name="start_price" step="0.01" min="0" value="<?php echo htmlspecialchars($item['start_price'] ?? ''); ?>" <?php echo $can_edit ? 'required' : 'readonly'; ?>>
                </div>
                
                <div class="form-group">
                    <label for="reserve_price">預期結標金額 <span class="required">*</span></label>
                    <input type="number" id="reserve_price" name="reserve_price" step="0.01" min="0" value="<?php echo htmlspecialchars($item['reserve_price'] ?? ''); ?>" <?php echo $can_edit ? 'required' : 'readonly'; ?>>
                </div>
                
                <div class="form-group">
                    <label for="remarks">其他備註</label>
                    <textarea id="remarks" name="remarks" <?php echo $can_edit ? '' : 'readonly'; ?>><?php echo htmlspecialchars($item['remarks'] ?? ''); ?></textarea>
                </div>
                
                <?php if ($can_edit): ?>
                    <button type="submit" class="btn">更新商品</button>
                <?php else: ?>
                    <button type="button" class="btn" disabled>商品已鎖定無法修改</button>
                <?php endif; ?>
            </form>
        </div>
    </div>
</body>
</html>
