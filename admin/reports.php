<?php
/**
 * 報表查詢頁面
 * 管理員可以查看各種統計報表
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

// 設定預設查詢條件
$report_type = $_GET['type'] ?? 'daily';
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$user_filter = intval($_GET['user_id'] ?? 0);

// 取得所有客戶列表（用於篩選）
$users_sql = "SELECT id, username FROM users WHERE role = 'customer' ORDER BY username";
$users_result = $conn->query($users_sql);

// 根據報表類型查詢數據
$report_data = [];
$total_records = 0;

try {
    switch ($report_type) {
        case 'daily':
            // 日報表
            $sql = "SELECT DATE(created_at) as report_date, 
                           COUNT(*) as item_count,
                           SUM(CASE WHEN status = 'sold' THEN 1 ELSE 0 END) as sold_count,
                           SUM(CASE WHEN status = 'sold' THEN final_price ELSE 0 END) as total_sales
                    FROM items 
                    WHERE DATE(created_at) BETWEEN ? AND ? ";
            
            $params = [$start_date, $end_date];
            $types = "ss";
            
            if ($user_filter > 0) {
                $sql .= "AND seller_id = ? ";
                $params[] = $user_filter;
                $types .= "i";
            }
            
            $sql .= "GROUP BY DATE(created_at) ORDER BY report_date DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $report_data[] = $row;
            }
            $total_records = count($report_data);
            break;
            
        case 'weekly':
            // 週報表
            $sql = "SELECT YEAR(created_at) as year, 
                           WEEK(created_at) as week,
                           CONCAT(YEAR(created_at), '-W', LPAD(WEEK(created_at), 2, '0')) as week_label,
                           COUNT(*) as item_count,
                           SUM(CASE WHEN status = 'sold' THEN 1 ELSE 0 END) as sold_count,
                           SUM(CASE WHEN status = 'sold' THEN final_price ELSE 0 END) as total_sales
                    FROM items 
                    WHERE DATE(created_at) BETWEEN ? AND ? ";
            
            $params = [$start_date, $end_date];
            $types = "ss";
            
            if ($user_filter > 0) {
                $sql .= "AND seller_id = ? ";
                $params[] = $user_filter;
                $types .= "i";
            }
            
            $sql .= "GROUP BY YEAR(created_at), WEEK(created_at) ORDER BY year DESC, week DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $report_data[] = $row;
            }
            $total_records = count($report_data);
            break;
            
        case 'monthly':
            // 月報表
            $sql = "SELECT YEAR(created_at) as year, 
                           MONTH(created_at) as month,
                           CONCAT(YEAR(created_at), '-', LPAD(MONTH(created_at), 2, '0')) as month_label,
                           COUNT(*) as item_count,
                           SUM(CASE WHEN status = 'sold' THEN 1 ELSE 0 END) as sold_count,
                           SUM(CASE WHEN status = 'sold' THEN final_price ELSE 0 END) as total_sales
                    FROM items 
                    WHERE DATE(created_at) BETWEEN ? AND ? ";
            
            $params = [$start_date, $end_date];
            $types = "ss";
            
            if ($user_filter > 0) {
                $sql .= "AND seller_id = ? ";
                $params[] = $user_filter;
                $types .= "i";
            }
            
            $sql .= "GROUP BY YEAR(created_at), MONTH(created_at) ORDER BY year DESC, month DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $report_data[] = $row;
            }
            $total_records = count($report_data);
            break;
            
        case 'user_summary':
            // 用戶彙總統計
            $sql = "SELECT u.username,
                           COUNT(i.id) as total_items,
                           SUM(CASE WHEN i.status = 'sold' THEN 1 ELSE 0 END) as sold_items,
                           SUM(CASE WHEN i.status = 'sold' THEN i.final_price ELSE 0 END) as total_sales,
                           AVG(CASE WHEN i.status = 'sold' THEN i.final_price ELSE NULL END) as avg_sale_price
                    FROM users u
                    LEFT JOIN items i ON u.id = i.seller_id
                    WHERE u.role = 'customer' ";
            
            $params = [];
            $types = "";
            
            if ($user_filter > 0) {
                $sql .= "AND u.id = ? ";
                $params[] = $user_filter;
                $types .= "i";
            }
            
            $sql .= "GROUP BY u.id, u.username ORDER BY total_sales DESC";
            
            $stmt = $conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $report_data[] = $row;
            }
            $total_records = count($report_data);
            break;
    }
} catch (Exception $e) {
    $error_message = "查詢報表時發生錯誤：" . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>報表查詢 - <?php echo SITE_NAME; ?></title>
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
        
        .filters {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: end;
            margin-bottom: 1rem;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: #343a40;
        }
        
        .filter-group select,
        .filter-group input {
            padding: 0.5rem;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .btn {
            background-color: #007cba;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            height: fit-content;
        }
        
        .btn:hover {
            background-color: #0056b3;
        }
        
        .report-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .report-table th {
            background-color: #007cba;
            color: white;
            padding: 1rem;
            text-align: left;
        }
        
        .report-table td {
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
        }
        
        .report-table tr:last-child td {
            border-bottom: none;
        }
        
        .report-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background-color: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            color: #6c757d;
            margin: 0 0 1rem 0;
            font-size: 1rem;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #007cba;
        }
        
        .no-data {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 2rem;
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
        
        @media (max-width: 768px) {
            .filter-row {
                flex-direction: column;
                align-items: stretch;
            }
            
            .report-table {
                font-size: 0.9rem;
            }
            
            .report-table th,
            .report-table td {
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <h1><?php echo SITE_NAME; ?> - 報表查詢</h1>
        <div class="nav-links">
            <a href="dashboard.php">儀表板</a>
            <a href="manage-items.php">商品管理</a>
            <a href="manage-users.php">用戶管理</a>
            <a href="../index.php">返回首頁</a>
            <a href="../logout.php">登出</a>
        </div>
    </header>
    
    <div class="container">
        <div class="page-header">
            <h2>報表查詢</h2>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div class="message error"><?php echo safeOutput($error_message); ?></div>
        <?php endif; ?>
        
        <div class="filters">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="type">報表類型：</label>
                        <select id="type" name="type">
                            <option value="daily" <?php echo $report_type === 'daily' ? 'selected' : ''; ?>>日報表</option>
                            <option value="weekly" <?php echo $report_type === 'weekly' ? 'selected' : ''; ?>>週報表</option>
                            <option value="monthly" <?php echo $report_type === 'monthly' ? 'selected' : ''; ?>>月報表</option>
                            <option value="user_summary" <?php echo $report_type === 'user_summary' ? 'selected' : ''; ?>>用戶彙總</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="start_date">開始日期：</label>
                        <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="end_date">結束日期：</label>
                        <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="user_id">用戶篩選：</label>
                        <select id="user_id" name="user_id">
                            <option value="0">全部用戶</option>
                            <?php 
                            $users_result->data_seek(0); // 重置結果指針
                            while ($user = $users_result->fetch_assoc()): ?>
                                <option value="<?php echo $user['id']; ?>" <?php echo $user_filter == $user['id'] ? 'selected' : ''; ?>>
                                    <?php echo safeOutput($user['username']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <button type="submit" class="btn">查詢</button>
                    </div>
                </div>
            </form>
        </div>
        
        <?php if (!empty($report_data)): ?>
            <!-- 顯示統計摘要 -->
            <?php
            $total_items = 0;
            $total_sold = 0;
            $total_sales = 0;
            
            foreach ($report_data as $data) {
                if (isset($data['item_count'])) {
                    $total_items += $data['item_count'];
                }
                if (isset($data['sold_count'])) {
                    $total_sold += $data['sold_count'];
                }
                if (isset($data['total_sales'])) {
                    $total_sales += $data['total_sales'];
                }
            }
            ?>
            
            <div class="summary-stats">
                <div class="stat-card">
                    <h3>總商品數</h3>
                    <div class="stat-value"><?php echo $total_items; ?></div>
                </div>
                <div class="stat-card">
                    <h3>已結標數</h3>
                    <div class="stat-value"><?php echo $total_sold; ?></div>
                </div>
                <div class="stat-card">
                    <h3>總銷售額</h3>
                    <div class="stat-value"><?php echo formatCurrency($total_sales); ?></div>
                </div>
            </div>
            
            <!-- 顯示報表數據 -->
            <table class="report-table">
                <thead>
                    <tr>
                        <?php switch ($report_type):
                            case 'daily': ?>
                                <th>日期</th>
                                <th>商品數量</th>
                                <th>已結標數</th>
                                <th>銷售額</th>
                            <?php break;
                            case 'weekly': ?>
                                <th>年份-週數</th>
                                <th>商品數量</th>
                                <th>已結標數</th>
                                <th>銷售額</th>
                            <?php break;
                            case 'monthly': ?>
                                <th>年份-月份</th>
                                <th>商品數量</th>
                                <th>已結標數</th>
                                <th>銷售額</th>
                            <?php break;
                            case 'user_summary': ?>
                                <th>用戶名稱</th>
                                <th>商品總數</th>
                                <th>已結標數</th>
                                <th>總銷售額</th>
                                <th>平均售價</th>
                            <?php break;
                        endswitch; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report_data as $data): ?>
                        <tr>
                            <?php switch ($report_type):
                                case 'daily': ?>
                                    <td><?php echo $data['report_date']; ?></td>
                                    <td><?php echo $data['item_count']; ?></td>
                                    <td><?php echo $data['sold_count']; ?></td>
                                    <td><?php echo formatCurrency($data['total_sales']); ?></td>
                                <?php break;
                                case 'weekly': ?>
                                    <td><?php echo $data['week_label']; ?></td>
                                    <td><?php echo $data['item_count']; ?></td>
                                    <td><?php echo $data['sold_count']; ?></td>
                                    <td><?php echo formatCurrency($data['total_sales']); ?></td>
                                <?php break;
                                case 'monthly': ?>
                                    <td><?php echo $data['month_label']; ?></td>
                                    <td><?php echo $data['item_count']; ?></td>
                                    <td><?php echo $data['sold_count']; ?></td>
                                    <td><?php echo formatCurrency($data['total_sales']); ?></td>
                                <?php break;
                                case 'user_summary': ?>
                                    <td><?php echo safeOutput($data['username']); ?></td>
                                    <td><?php echo $data['total_items']; ?></td>
                                    <td><?php echo $data['sold_items']; ?></td>
                                    <td><?php echo formatCurrency($data['total_sales']); ?></td>
                                    <td><?php echo $data['avg_sale_price'] ? formatCurrency($data['avg_sale_price']) : 'N/A'; ?></td>
                                <?php break;
                            endswitch; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data">目前沒有符合條件的報表數據</div>
        <?php endif; ?>
    </div>
</body>
</html>
