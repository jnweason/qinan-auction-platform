<?php
/**
 * 客戶商品管理頁面
 * 客戶可以查看和管理自己的商品
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

// 處理刪除請求
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    try {
        // 檢查商品是否屬於當前用戶且狀態為待刊登
        $check_sql = "SELECT status FROM items WHERE id = ? AND seller_id = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("ii", $delete_id, $current_user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $item = $result->fetch_assoc();
            if ($item['status'] !== 'pending') {
                $error_message = "只有待刊登的商品才能刪除！";
            } else {
                // 開始交易
                $conn->begin_transaction();
                
                // 刪除相關的圖片記錄
                $delete_images_sql = "DELETE FROM images WHERE item_id = ?";
                $stmt = $conn->prepare($delete_images_sql);
                $stmt->bind_param("i", $delete_id);
                $stmt->execute();
                
                // 刪除相關的出價記錄
                $delete_bids_sql = "DELETE FROM bids WHERE item_id = ?";
                $stmt = $conn->prepare($delete_bids_sql);
                $stmt->bind_param("i", $delete_id);
                $stmt->execute();
                
                // 刪除商品
                $delete_item_sql = "DELETE FROM items WHERE id = ?";
                $stmt = $conn->prepare($delete_item_sql);
                $stmt->bind_param("i", $delete_id);
                $stmt->execute();
                
                // 提交交易
                $conn->commit();
                
                $success_message = "商品刪除成功！";
            }
        } else {
            $error_message = "商品不存在或不屬於您！";
        }
    } catch (Exception $e) {
        // 回滾交易
        $conn->rollback();
        $error_message = "刪除商品時發生錯誤：" . $e->getMessage();
    }
}

// 設定分頁參數
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$page_size = isset($_GET['size']) ? intval($_GET['size']) : 10;
$page_size = in_array($page_size, PAGE_SIZES) ? $page_size : 10;
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

$offset = ($page - 1) * $page_size;

// 構建查詢條件
$where_clause = "WHERE i.seller_id = ?";
$params = [$current_user_id];
$types = "i";

if ($status_filter !== 'all') {
    $where_clause .= " AND i.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// 查詢商品總數
$count_sql = "SELECT COUNT(*) as total FROM items i " . $where_clause;
$stmt = $conn->prepare($count_sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$total_records = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $page_size);

// 查詢商品資料
$sql = "SELECT i.*, 
               (SELECT MAX(amount) FROM bids WHERE item_id = i.id) as highest_bid,
               (SELECT COUNT(*) FROM bids WHERE item_id = i.id) as bid_count
        FROM items i 
        " . $where_clause . " 
        ORDER BY i.created_at DESC 
        LIMIT ? OFFSET ?";

$params[] = $page_size;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>我的商品 - <?php echo SITE_NAME; ?></title>
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
        
        .filters {
            background-color: white;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .filter-group label {
            font-weight: bold;
        }
        
        .filter-group select {
            padding: 0.5rem;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .items-table th {
            background-color: #007cba;
            color: white;
            padding: 1rem;
            text-align: left;
        }
        
        .items-table td {
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
        }
        
        .items-table tr:last-child td {
            border-bottom: none;
        }
        
        .items-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
        }
        
        .status-active {
            background-color: #d4edda;
            color: #155724;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
        }
        
        .status-sold {
            background-color: #cce5ff;
            color: #004085;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
        }
        
        .status-unsold {
            background-color: #f8d7da;
            color: #721c24;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
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
        
        .edit-disabled {
            color: #6c757d !important;
            cursor: not-allowed;
        }
        
        .edit-disabled:hover {
            text-decoration: none !important;
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
            .items-table {
                font-size: 0.9rem;
            }
            
            .items-table th,
            .items-table td {
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
        <h1><?php echo SITE_NAME; ?> - 我的商品</h1>
        <div class="nav-links">
            <a href="dashboard.php">儀表板</a>
            <a href="../index.php">首頁</a>
            <a href="profile.php">個人資料</a>
            <a href="../logout.php">登出</a>
        </div>
    </header>
    
    <div class="container">
        <div class="page-header">
            <div class="page-title">
                <h2>我的商品</h2>
            </div>
            <a href="add-item.php" class="add-button">新增商品</a>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div class="message error"><?php echo safeOutput($error_message); ?></div>
        <?php endif; ?>
        
        <?php if (isset($success_message)): ?>
            <div class="message success"><?php echo safeOutput($success_message); ?></div>
        <?php endif; ?>
        
        <div class="filters">
            <div class="filter-group">
                <label for="status_filter">狀態篩選：</label>
                <select id="status_filter" onchange="location.href='?status='+this.value+'&page=1'">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>全部</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>待刊登</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>刊登中</option>
                    <option value="sold" <?php echo $status_filter === 'sold' ? 'selected' : ''; ?>>已結標</option>
                    <option value="unsold" <?php echo $status_filter === 'unsold' ? 'selected' : ''; ?>>已流標</option>
                </select>
            </div>
        </div>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th>編號</th>
                    <th>商品名稱</th>
                    <th>開價</th>
                    <th>目前出價</th>
                    <th>預期結標金額</th>
                    <th>出價次數</th>
                    <th>狀態</th>
                    <th>建立時間</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo safeOutput($row['name']); ?></td>
                            <td><?php echo formatCurrency($row['start_price']); ?></td>
                            <td><?php echo $row['highest_bid'] > 0 ? formatCurrency($row['highest_bid']) : '無'; ?></td>
                            <td><?php echo formatCurrency($row['reserve_price']); ?></td>
                            <td><?php echo $row['bid_count']; ?> 次</td>
                            <td>
                                <span class="status-<?php echo $row['status']; ?>">
                                    <?php 
                                    switch ($row['status']) {
                                        case 'pending': echo '待刊登'; break;
                                        case 'active': echo '刊登中'; break;
                                        case 'sold': echo '已結標'; break;
                                        case 'unsold': echo '已流標'; break;
                                        default: echo '未知';
                                    }
                                    ?>
                                </span>
                            </td>
                            <td><?php echo formatDateTime($row['created_at']); ?></td>
                            <td class="action-links">
                                <?php if ($row['status'] === 'pending'): ?>
                                    <a href="edit-item.php?id=<?php echo $row['id']; ?>">編輯</a>
                                    <a href="upload-images.php?item_id=<?php echo $row['id']; ?>">圖片</a>
                                    <a href="?delete_id=<?php echo $row['id']; ?>" 
                                       class="delete-link" 
                                       onclick="return confirm('確定要刪除此商品嗎？')">刪除</a>
                                <?php elseif ($row['status'] === 'active'): ?>
                                    <a href="upload-images.php?item_id=<?php echo $row['id']; ?>">圖片</a>
                                    <span class="edit-disabled" title="刊登中的商品無法編輯">編輯</span>
                                    <span class="edit-disabled" title="刊登中的商品無法刪除">刪除</span>
                                <?php else: ?>
                                    <a href="upload-images.php?item_id=<?php echo $row['id']; ?>">圖片</a>
                                    <span class="edit-disabled" title="已結標/流標的商品無法編輯">編輯</span>
                                    <span class="edit-disabled" title="已結標/流標的商品無法刪除">刪除</span>
                                <?php endif; ?>
                                <a href="../item-detail.php?id=<?php echo $row['id']; ?>">查看</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align: center;">目前沒有商品資料</td>
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
                    <select onchange="location.href='?status=<?php echo $status_filter; ?>&page=1&size='+this.value">
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
                        <a href="?status=<?php echo $status_filter; ?>&page=<?php echo $page - 1; ?>&size=<?php echo $page_size; ?>">&laquo; 上一頁</a>
                    <?php endif; ?>
                    
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    if ($start_page > 1): ?>
                        <a href="?status=<?php echo $status_filter; ?>&page=1&size=<?php echo $page_size; ?>">1</a>
                        <?php if ($start_page > 2): ?>
                            <span>...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?status=<?php echo $status_filter; ?>&page=<?php echo $i; ?>&size=<?php echo $page_size; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <span>...</span>
                        <?php endif; ?>
                        <a href="?status=<?php echo $status_filter; ?>&page=<?php echo $total_pages; ?>&size=<?php echo $page_size; ?>"><?php echo $total_pages; ?></a>
                    <?php endif; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?status=<?php echo $status_filter; ?>&page=<?php echo $page + 1; ?>&size=<?php echo $page_size; ?>">下一頁 &raquo;</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
