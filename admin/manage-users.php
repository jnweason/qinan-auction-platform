<?php
/**
 * 用戶管理頁面
 * 管理員可以管理客戶帳號
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

// 處理刪除用戶請求
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // 不能刪除自己和管理員帳號
    if ($delete_id == $_SESSION['user_id']) {
        $error_message = "不能刪除自己的帳號！";
    } else {
        try {
            // 檢查用戶是否存在且為客戶
            $check_sql = "SELECT role FROM users WHERE id = ?";
            $stmt = $conn->prepare($check_sql);
            $stmt->bind_param("i", $delete_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                if ($user['role'] === 'admin') {
                    $error_message = "不能刪除管理員帳號！";
                } else {
                    // 開始交易
                    $conn->begin_transaction();
                    
                    // 刪除用戶相關的圖片記錄
                    $delete_images_sql = "DELETE FROM images WHERE item_id IN (SELECT id FROM items WHERE seller_id = ?)";
                    $stmt = $conn->prepare($delete_images_sql);
                    $stmt->bind_param("i", $delete_id);
                    $stmt->execute();
                    
                    // 刪除用戶相關的出價記錄
                    $delete_bids_sql = "DELETE FROM bids WHERE bidder_id = ?";
                    $stmt = $conn->prepare($delete_bids_sql);
                    $stmt->bind_param("i", $delete_id);
                    $stmt->execute();
                    
                    // 刪除用戶相關的商品
                    $delete_items_sql = "DELETE FROM items WHERE seller_id = ?";
                    $stmt = $conn->prepare($delete_items_sql);
                    $stmt->bind_param("i", $delete_id);
                    $stmt->execute();
                    
                    // 刪除用戶帳號
                    $delete_user_sql = "DELETE FROM users WHERE id = ? AND role = 'customer'";
                    $stmt = $conn->prepare($delete_user_sql);
                    $stmt->bind_param("i", $delete_id);
                    $stmt->execute();
                    
                    // 提交交易
                    $conn->commit();
                    
                    $success_message = "用戶刪除成功！";
                }
            } else {
                $error_message = "用戶不存在！";
            }
        } catch (Exception $e) {
            // 回滾交易
            $conn->rollback();
            $error_message = "刪除用戶時發生錯誤：" . $e->getMessage();
        }
    }
}

// 設定分頁參數
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$page_size = isset($_GET['size']) ? intval($_GET['size']) : 10;
$page_size = in_array($page_size, PAGE_SIZES) ? $page_size : 10;

$offset = ($page - 1) * $page_size;

// 查詢用戶總數
$count_sql = "SELECT COUNT(*) as total FROM users WHERE role = 'customer'";
$count_result = $conn->query($count_sql);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $page_size);

// 查詢用戶資料
$sql = "SELECT u.*, 
               (SELECT COUNT(*) FROM items WHERE seller_id = u.id) as item_count,
               (SELECT COALESCE(SUM(final_price), 0) FROM items WHERE seller_id = u.id AND status = 'sold') as total_sales
        FROM users u 
        WHERE u.role = 'customer' 
        ORDER BY u.created_at DESC 
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $page_size, $offset);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用戶管理 - <?php echo SITE_NAME; ?></title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .page-title h2 {
            color: #343a40;
            margin: 0;
        }
        
        .add-button {
            background-color: #28a745;
            color: white;
            padding: 0.5rem 1rem;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .add-button:hover {
            background-color: #218838;
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .users-table th {
            background-color: #007cba;
            color: white;
            padding: 1rem;
            text-align: left;
        }
        
        .users-table td {
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
        }
        
        .users-table tr:last-child td {
            border-bottom: none;
        }
        
        .users-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .action-links a {
            color: #007cba;
            text-decoration: none;
            margin-right: 10px;
        }
        
        .action-links a:hover {
            text-decoration: underline;
        }
        
        .delete-link {
            color: #dc3545 !important;
        }
        
        .delete-link:hover {
            text-decoration: underline !important;
        }
        
        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .pagination-info {
            color: #6c757d;
        }
        
        .pagination-links a {
            display: inline-block;
            padding: 8px 12px;
            margin: 0 2px;
            background-color: #007cba;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        
        .pagination-links a:hover {
            background-color: #0056b3;
        }
        
        .pagination-links .current {
            background-color: #28a745;
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
        }
        
        .page-size-selector {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .page-size-selector select {
            padding: 5px;
            border: 1px solid #ced4da;
            border-radius: 4px;
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
        
        @media (max-width: 768px) {
            .users-table {
                font-size: 0.9rem;
            }
            
            .users-table th,
            .users-table td {
                padding: 0.5rem;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <h1><?php echo SITE_NAME; ?> - 用戶管理</h1>
        <div class="nav-links">
            <a href="dashboard.php">儀表板</a>
            <a href="manage-items.php">商品管理</a>
            <a href="../index.php">返回首頁</a>
            <a href="reports.php">報表查詢</a>
            <a href="../logout.php">登出</a>
        </div>
    </header>
    
    <div class="container">
        <div class="page-header">
            <div class="page-title">
                <h2>用戶管理</h2>
            </div>
            <a href="add-user.php" class="add-button">新增用戶</a>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div class="message error"><?php echo safeOutput($error_message); ?></div>
        <?php endif; ?>
        
        <?php if (isset($success_message)): ?>
            <div class="message success"><?php echo safeOutput($success_message); ?></div>
        <?php endif; ?>
        
        <table class="users-table">
            <thead>
                <tr>
                    <th>用戶名稱</th>
                    <th>帳戶餘額</th>
                    <th>商品數量</th>
                    <th>總銷售額</th>
                    <th>註冊時間</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo safeOutput($row['username']); ?></td>
                            <td><?php echo formatCurrency($row['balance']); ?></td>
                            <td><?php echo $row['item_count']; ?></td>
                            <td><?php echo formatCurrency($row['total_sales']); ?></td>
                            <td><?php echo formatDateTime($row['created_at']); ?></td>
                            <td class="action-links">
                                <a href="edit-user.php?id=<?php echo $row['id']; ?>">編輯</a>
                                <a href="?delete_id=<?php echo $row['id']; ?>" 
                                   class="delete-link" 
                                   onclick="return confirm('確定要刪除此用戶嗎？此操作將刪除所有相關資料且無法復原！')">刪除</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">目前沒有用戶資料</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if ($total_records > 0): ?>
            <div class="pagination">
                <div class="pagination-info">
                    顯示第 <?php echo ($offset + 1); ?> - <?php echo min($offset + $page_size, $total_records); ?> 筆，
                    共 <?php echo $total_records; ?> 筆記錄
                </div>
                
                <div class="page-size-selector">
                    <span>每頁顯示：</span>
                    <select onchange="location.href='?page=1&size='+this.value">
                        <?php foreach (PAGE_SIZES as $size): ?>
                            <option value="<?php echo $size; ?>" <?php echo $size == $page_size ? 'selected' : ''; ?>>
                                <?php echo $size; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span>筆</span>
                </div>
                
                <div class="pagination-links">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&size=<?php echo $page_size; ?>">&laquo; 上一頁</a>
                    <?php endif; ?>
                    
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    if ($start_page > 1): ?>
                        <a href="?page=1&size=<?php echo $page_size; ?>">1</a>
                        <?php if ($start_page > 2): ?>
                            <span>...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>&size=<?php echo $page_size; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <span>...</span>
                        <?php endif; ?>
                        <a href="?page=<?php echo $total_pages; ?>&size=<?php echo $page_size; ?>"><?php echo $total_pages; ?></a>
                    <?php endif; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&size=<?php echo $page_size; ?>">下一頁 &raquo;</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
