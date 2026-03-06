<?php
/**
 * 即時拍賣列表頁面
 * 顯示所有正在拍賣的商品
 */

// 啟動 Session
session_start();

// 引入必要檔案
include "includes/db_connect.php";
include "includes/functions.php";

// 設定分頁參數
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$page_size = isset($_GET['size']) ? intval($_GET['size']) : 10;
$page_size = in_array($page_size, [10, 20, 30]) ? $page_size : 10;
$offset = ($page - 1) * $page_size;

// 設定搜尋和篩選參數
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all'; // 新增狀態篩選

// 構建查詢條件
$where_conditions = "1=1"; // 修改為顯示所有狀態的商品
$params = [];
$types = "";

// 狀態篩選條件
if ($status_filter !== 'all') {
    $where_conditions .= " AND i.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// 搜尋條件
if (!empty($search)) {
    $where_conditions .= " AND (i.id = ? OR i.name LIKE ? OR i.start_price = ?)";
    $params[] = $search;
    $params[] = '%' . $search . '%';
    $params[] = $search;
    $types .= "sss";
}

// 日期篩選條件
if (!empty($start_date)) {
    $where_conditions .= " AND DATE(i.created_at) >= ?";
    $params[] = $start_date;
    $types .= "s";
}

if (!empty($end_date)) {
    $where_conditions .= " AND DATE(i.created_at) <= ?";
    $params[] = $end_date;
    $types .= "s";
}

// 查詢商品（修改為顯示所有狀態）
$sql = "SELECT i.id, i.name, i.current_bid, i.reserve_price, i.start_price, i.created_at, i.status,
               (SELECT MAX(amount) FROM bids WHERE item_id = i.id) as highest_bid,
               (SELECT COUNT(*) FROM bids WHERE item_id = i.id) as bid_count,
               (SELECT file_path FROM images WHERE item_id = i.id LIMIT 1) as image_path
        FROM items i 
        WHERE $where_conditions
        ORDER BY i.created_at DESC 
        LIMIT ? OFFSET ?";

// 添加分頁參數
$params[] = $page_size;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// 查詢總數
$count_sql = "SELECT COUNT(*) as total FROM items i WHERE $where_conditions";
$count_stmt = $conn->prepare($count_sql);
if (!empty($types) && strlen($types) > 2) { // 排除分頁參數
    $count_types = substr($types, 0, strlen($types) - 2);
    $count_params = array_slice($params, 0, count($params) - 2);
    if (!empty($count_types)) {
        $count_stmt->bind_param($count_types, ...$count_params);
    }
}
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $page_size);

// 獲取最新的真實動態數據
$activity_sql = "SELECT 
                    u.username as user_name,
                    i.name as item_name,
                    b.amount as bid_amount,
                    i.id as item_id,
                    '出價' as action_type,
                    b.bid_time as activity_time
                FROM bids b
                JOIN users u ON b.bidder_id = u.id
                JOIN items i ON b.item_id = i.id
                WHERE b.bid_time >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                UNION
                SELECT 
                    u.username as user_name,
                    i.name as item_name,
                    i.start_price as bid_amount,
                    i.id as item_id,
                    '刊登' as action_type,
                    i.created_at as activity_time
                FROM items i
                JOIN users u ON i.seller_id = u.id
                WHERE i.created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                UNION
                SELECT 
                    u.username as user_name,
                    i.name as item_name,
                    i.final_price as bid_amount,
                    i.id as item_id,
                    '結標' as action_type,
                    i.created_at as activity_time
                FROM items i
                JOIN users u ON i.seller_id = u.id
                WHERE i.status = 'sold' AND i.created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                ORDER BY activity_time DESC
                LIMIT 10";

$activity_result = $conn->query($activity_sql);

// 中文百家姓
$chinese_surnames = ['王', '李', '張', '劉', '陳', '楊', '趙', '黃', '周', '吳', 
                    '徐', '孫', '胡', '朱', '高', '林', '何', '郭', '馬', '羅',
                    '梁', '宋', '鄭', '謝', '韓', '唐', '馮', '于', '董', '蕭'];

// 英文字母
$english_letters = range('A', 'Z');

?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>即時拍賣 - 奇楠沉香交易拍賣平台</title>
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
            position: relative;
        }
        
        .header h1 {
            margin: 0;
            font-size: 1.5rem;
        }
        
        /* 隱藏傳統導航連結 */
        .nav-links {
            display: none;
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
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .page-title h2 {
            color: #343a40;
            margin: 0;
        }
        
        .filters {
            background-color: white;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
        }
        
        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: end;
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
        
        .filter-group input, .filter-group select {
            padding: 0.5rem;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }
        
        .btn {
            background-color: #007cba;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            height: fit-content;
            font-size: 16px;
            min-width: 80px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            text-align: center;
            box-sizing: border-box;
        }
        
        .btn:hover {
            background-color: #0056b3;
        }
        
        .reset-btn {
            background-color: #6c757d !important;
        }
        
        .auction-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .auction-table th {
            background-color: #007cba;
            color: white;
            padding: 1rem;
            text-align: left;
        }
        
        .auction-table td {
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
        }
        
        .auction-table tr:last-child td {
            border-bottom: none;
        }
        
        /* 商品狀態底色 */
        .auction-table tr.status-active {
            background-color: white;
        }

        .auction-table tr.status-sold {
            background-color: #f0f0f0;
            color: #333;
        }

        .auction-table tr.status-unsold {
            background-color: #ffebee;
            color: #c62828;
        }

        .auction-table tr.status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .auction-table tr.status-active:hover {
            background-color: #f8f9fa;
        }

        .auction-table tr.status-sold:hover {
            background-color: #e0e0e0;
        }

        .auction-table tr.status-unsold:hover {
            background-color: #ffcdd2;
        }

        .auction-table tr.status-pending:hover {
            background-color: #fff0b3;
        }
        
        .item-link {
            color: #007cba;
            text-decoration: none;
            font-weight: bold;
        }
        
        .item-link:hover {
            text-decoration: underline;
        }
        
        .price {
            font-weight: bold;
            color: #28a745;
        }
        
        .no-bids {
            color: #6c757d;
            font-style: italic;
        }
        
        .item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #dee2e6;
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
            padding: 8px 12px;
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
        
        /* 懸浮視窗樣式 */
        .activity-popup {
            position: fixed;
            top: 20px;
            left: 20px;
            width: 300px;
            max-height: 400px;
            background-color: white;
            border: 2px solid #007cba;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            z-index: 1000;
            overflow: hidden;
            font-family: Arial, sans-serif;
        }
        
        .activity-header {
            background-color: #007cba;
            color: white;
            padding: 10px 15px;
            font-weight: bold;
            cursor: move;
            display: flex;
            justify-content: space-between;
            align-items: center;
            user-select: none;
        }
        
        .toggle-btn {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            user-select: none;
        }
        
        .activity-content {
            padding: 10px;
            max-height: 350px;
            overflow-y: auto;
        }
        
        .activity-content.collapsed {
            display: none;
        }
        
        .activity-item {
            padding: 8px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-time {
            color: #666;
            font-size: 12px;
            margin-top: 3px;
        }
        
        .close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            user-select: none;
        }
        
        .button-group {
            display: flex;
            gap: 0.5rem;
            align-items: end;
        }

        .status-legend {
            display: flex;
            gap: 20px;
            margin: 15px 0;
            flex-wrap: wrap;
        }

        .status-item {
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
        }

        .status-item div {
            width: 20px;
            height: 20px;
            border: 1px solid #ccc;
        }

        .status-active-sample {
            background-color: white;
        }

        .status-sold-sample {
            background-color: #f0f0f0;
        }

        .status-unsold-sample {
            background-color: #ffebee;
        }
        
        .status-pending-sample {
            background-color: #fff3cd;
        }
        
        /* 按鈕容器樣式調整 */
        .buttons-container {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        /* 手機版導航菜單 */
        .mobile-menu-toggle {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .mobile-nav {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background-color: #343a40;
            width: 250px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            z-index: 1001;
            border-radius: 0 0 8px 8px;
        }
        
        .mobile-nav.show {
            display: block;
        }
        
        .mobile-nav a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 15px 20px;
            border-bottom: 1px solid #495057;
        }
        
        .mobile-nav a:hover {
            background-color: #495057;
        }
        
        .mobile-nav a:last-child {
            border-bottom: none;
        }
        
        /* 篩選結果顯示 */
        .filter-results {
            background-color: #e9f7ef;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            border: 1px solid #c3e6cb;
        }
        
        @media (max-width: 768px) {
            .auction-table {
                font-size: 0.9rem;
            }
            
            .auction-table th,
            .auction-table td {
                padding: 0.5rem;
            }
            
            .activity-popup {
                width: 250px;
                left: 10px;
                top: 10px;
                max-width: 90vw;
            }
            
            .button-group {
                flex-direction: row;
                width: 100%;
            }
            
            .button-group .btn {
                flex: 1;
            }
            
            .filter-row {
                flex-direction: column;
                align-items: stretch;
            }
            
            /* 在手機上確保按鈕水平排列 */
            .buttons-container {
                flex-direction: row;
                width: 100%;
            }
            
            .buttons-container .btn {
                flex: 1;
                min-height: 44px;
                font-size: 16px;
            }
        }   
    </style>
    <script>
    // 懸浮視窗拖拽功能（支援手機觸控）
    function makeDraggable(element) {
        let pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
        let isDragging = false;
        
        if (document.getElementById(element.id + "header")) {
            const header = document.getElementById(element.id + "header");
            
            // 鼠標事件
            header.onmousedown = dragMouseDown;
            
            // 觸控事件（手機支援）
            header.addEventListener('touchstart', touchStart, { passive: false });
        }
        
        function dragMouseDown(e) {
            e = e || window.event;
            e.preventDefault();
            pos3 = e.clientX;
            pos4 = e.clientY;
            document.onmouseup = closeDragElement;
            document.onmousemove = elementDrag;
        }
        
        function touchStart(e) {
            e.preventDefault();
            const touch = e.touches[0];
            pos3 = touch.clientX;
            pos4 = touch.clientY;
            document.ontouchend = closeDragElement;
            document.ontouchmove = touchMove;
        }
        
        function touchMove(e) {
            e.preventDefault();
            const touch = e.touches[0];
            pos1 = pos3 - touch.clientX;
            pos2 = pos4 - touch.clientY;
            pos3 = touch.clientX;
            pos4 = touch.clientY;
            element.style.top = (element.offsetTop - pos2) + "px";
            element.style.left = (element.offsetLeft - pos1) + "px";
        }
        
        function elementDrag(e) {
            e = e || window.event;
            e.preventDefault();
            pos1 = pos3 - e.clientX;
            pos2 = pos4 - e.clientY;
            pos3 = e.clientX;
            pos4 = e.clientY;
            element.style.top = (element.offsetTop - pos2) + "px";
            element.style.left = (element.offsetLeft - pos1) + "px";
        }
        
        function closeDragElement() {
            document.onmouseup = null;
            document.onmousemove = null;
            document.ontouchend = null;
            document.ontouchmove = null;
        }
    }
    
    // 切換懸浮視窗展開/收納
    function toggleActivityPopup() {
        const content = document.getElementById('activityContent');
        const toggleBtn = document.getElementById('toggleBtn');
        const isCollapsed = content.classList.contains('collapsed');
        
        if (isCollapsed) {
            content.classList.remove('collapsed');
            toggleBtn.textContent = '−';
        } else {
            content.classList.add('collapsed');
            toggleBtn.textContent = '+';
        }
    }
    
    // 關閉懸浮視窗（僅在電腦版顯示）
    function closeActivityPopup() {
        document.getElementById('activityPopup').style.display = 'none';
    }
    
    // 切換手機版導航菜單
    function toggleMobileMenu() {
        const mobileNav = document.getElementById('mobileNav');
        mobileNav.classList.toggle('show');
    }
    
    // 點擊外部關閉手機菜單
    document.addEventListener('click', function(event) {
        const mobileNav = document.getElementById('mobileNav');
        const menuToggle = document.querySelector('.mobile-menu-toggle');
        
        if (mobileNav.classList.contains('show') && 
            !mobileNav.contains(event.target) && 
            event.target !== menuToggle) {
            mobileNav.classList.remove('show');
        }
    });
    
    // 點擊狀態圖例進行篩選
    function filterByStatus(status) {
        const statusSelect = document.getElementById('status');
        statusSelect.value = status;
        statusSelect.form.submit();
    }
    
    // 生成虛擬動態數據
    function generateVirtualActivity() {
        // 中文姓氏
        const chineseSurnames = ['王', '李', '張', '劉', '陳', '楊', '趙', '黃', '周', '吳', 
                            '徐', '孫', '胡', '朱', '高', '林', '何', '郭', '馬', '羅',
                            '梁', '宋', '鄭', '謝', '韓', '唐', '馮', '于', '董', '蕭',
                            '程', '曹', '袁', '鄧', '許', '傅', '沈', '曾', '彭', '呂',
                            '蘇', '盧', '蔣', '蔡', '賈', '丁', '魏', '薛', '葉', '閻'];
        
        // 英文姓氏
        const englishSurnames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez',
                                'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson', 'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin',
                                'Lee', 'Perez', 'Thompson', 'White', 'Harris', 'Sanchez', 'Clark', 'Ramirez', 'Lewis', 'Robinson',
                                'Walker', 'Young', 'Allen', 'King', 'Wright', 'Scott', 'Torres', 'Nguyen', 'Hill', 'Flores',
                                'Green', 'Adams', 'Nelson', 'Baker', 'Hall', 'Rivera', 'Campbell', 'Mitchell', 'Carter', 'Roberts'];
        
        const actions = ['刊登商品', '對商品出價', '商品已結標'];
        const items = ['奇楠沉香手串', '沉香木原料', '奇楠沉香1年期', '奇楠沉香4年期', '奇楠沉香8年期',
                    '海南沉香', '越南沉香', '印尼沉香', '馬來西亞沉香', '奇楠沉香粉',
                    '沉香香爐', '奇楠沉香珠', '沉香雕件', '奇楠沉香片', '天然沉香'];
        
        // 隨機選擇中英文用戶
        let userName = '';
        if (Math.random() > 0.5) {
            // 中文用戶
            const surname = chineseSurnames[Math.floor(Math.random() * chineseSurnames.length)];
            userName = surname + '**';
        } else {
            // 英文用戶
            const surname = englishSurnames[Math.floor(Math.random() * englishSurnames.length)];
            userName = surname.charAt(0) + '*'.repeat(Math.max(1, surname.length - 1));
        }
        
        const action = actions[Math.floor(Math.random() * actions.length)];
        const item = items[Math.floor(Math.random() * items.length)];
        
        // 只顯示小時和分鐘
        const now = new Date();
        const time = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
        
        let message = '';
        if (action === '刊登商品') {
            message = `${userName} 委託刊登了 ${item}`;
        } else if (action === '對商品出價') {
            // 修改為10000的倍數，且不小於10000，並顯示.00小數點
            const price = Math.floor((Math.random() * 900000 + 10000) / 10000) * 10000;
            message = `${userName} 對 ${item} 委託出價 NT$ ${price.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        } else {
            message = `${userName} 的 ${item} 已結標`;
        }
        
        return {message: message, time: time};
    }   
    
    // 添加虛擬活動到列表
    function addVirtualActivity() {
        const virtualActivity = generateVirtualActivity();
        const activityContent = document.querySelector('#activityContent');
        
        if (activityContent.children.length >= 20) {
            activityContent.removeChild(activityContent.lastChild);
        }
        
        const activityItem = document.createElement('div');
        activityItem.className = 'activity-item';
        activityItem.innerHTML = `
            <div>${virtualActivity.message}</div>
            <div class="activity-time">${virtualActivity.time}</div>
        `;
        
        activityContent.insertBefore(activityItem, activityContent.firstChild);
    }
    
    // 初始化
    document.addEventListener('DOMContentLoaded', function() {
        // 設置懸浮視窗可拖拽（支援手機）
        makeDraggable(document.getElementById('activityPopup'));
        
        // 每15-35秒生成一次虛擬活動
        setInterval(function() {
            const shouldGenerate = Math.random() > 0.3; // 70% 機率生成
            if (shouldGenerate) {
                addVirtualActivity();
            }
        }, Math.floor(Math.random() * 20000) + 15000); // 15-35秒
        
        // 為電腦版添加點擊標題收納功能
        const activityHeader = document.getElementById('activityPopupheader');
        if (window.innerWidth > 768) {
            activityHeader.addEventListener('click', function(e) {
                // 如果點擊的是按鈕則不觸發
                if (e.target.classList.contains('toggle-btn') || e.target.classList.contains('close-btn')) {
                    return;
                }
                toggleActivityPopup();
            });
        }
        
        // 為狀態圖例添加點擊事件
        document.querySelectorAll('.status-item').forEach(item => {
            item.addEventListener('click', function() {
                const statusText = this.querySelector('span').textContent;
                let statusValue = 'all';
                
                switch(statusText) {
                    case '待刊登':
                        statusValue = 'pending';
                        break;
                    case '已刊登':
                        statusValue = 'active';
                        break;
                    case '已結標':
                        statusValue = 'sold';
                        break;
                    case '已流標':
                        statusValue = 'unsold';
                        break;
                }
                
                if (statusValue !== 'all') {
                    filterByStatus(statusValue);
                }
            });
        });
    });
    </script> 
</head>
<body>
    <!-- 懸浮視窗 -->
    <div id="activityPopup" class="activity-popup">
        <div id="activityPopupheader" class="activity-header">
            <span>即時交易動態</span>
            <div>
                <button class="toggle-btn" id="toggleBtn" onclick="toggleActivityPopup()">−</button>
                <button class="close-btn" onclick="closeActivityPopup()" id="closeBtn">×</button>
            </div>
        </div>
        <div id="activityContent" class="activity-content">
            <?php 
            // 顯示真實活動
            if ($activity_result && $activity_result->num_rows > 0) {
                while ($activity = $activity_result->fetch_assoc()) {
                    $first_char = mb_substr($activity['user_name'], 0, 1, 'UTF-8');
                    $masked_name = $first_char . str_repeat('*', mb_strlen($activity['user_name'], 'UTF-8') - 1);
                    $time = date('H:i', strtotime($activity['activity_time']));
                    
                    echo '<div class="activity-item">';
                    switch ($activity['action_type']) {
                        case '出價':
                            echo '<div>' . htmlspecialchars($masked_name) . ' 對 ' . htmlspecialchars($activity['item_name']) . ' 委託出價 ' . formatCurrency($activity['bid_amount']) . '</div>';
                            break;
                        case '刊登':
                            echo '<div>' . htmlspecialchars($masked_name) . ' 委託刊登了 ' . htmlspecialchars($activity['item_name']) . '</div>';
                            break;
                        case '結標':
                            echo '<div>' . htmlspecialchars($masked_name) . ' 的 ' . htmlspecialchars($activity['item_name']) . ' 已結標</div>';
                            break;
                    }
                    echo '<div class="activity-time">' . $time . '</div>';
                    echo '</div>';
                }
            }
            
            // 中文姓氏
            $chineseSurnames = ['王', '李', '張', '劉', '陳', '楊', '趙', '黃', '周', '吳', 
                            '徐', '孫', '胡', '朱', '高', '林', '何', '郭', '馬', '羅',
                            '梁', '宋', '鄭', '謝', '韓', '唐', '馮', '于', '董', '蕭',
                            '程', '曹', '袁', '鄧', '許', '傅', '沈', '曾', '彭', '呂',
                            '蘇', '盧', '蔣', '蔡', '賈', '丁', '魏', '薛', '葉', '閻'];

            // 英文姓氏
            $englishSurnames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez',
                            'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson', 'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin',
                            'Lee', 'Perez', 'Thompson', 'White', 'Harris', 'Sanchez', 'Clark', 'Ramirez', 'Lewis', 'Robinson',
                            'Walker', 'Young', 'Allen', 'King', 'Wright', 'Scott', 'Torres', 'Nguyen', 'Hill', 'Flores',
                            'Green', 'Adams', 'Nelson', 'Baker', 'Hall', 'Rivera', 'Campbell', 'Mitchell', 'Carter', 'Roberts'];

            // 商品列表
            $itemsList = ['奇楠沉香手串', '沉香木原料', '奇楠沉香1年期', '奇楠沉香4年期', '奇楠沉香8年期',
                        '海南沉香', '越南沉香', '印尼沉香', '馬來西亞沉香', '奇楠沉香粉',
                        '沉香香爐', '奇楠沉香珠', '沉香雕件', '奇楠沉香片', '天然沉香'];

            // 生成一些初始虛擬活動
            for ($i = 0; $i < 5; $i++) {
                // 隨機選擇中英文用戶
                $userName = '';
                if (rand(0, 1) == 1) {
                    // 中文用戶
                    $surname = $chineseSurnames[array_rand($chineseSurnames)];
                    $userName = $surname . '**';
                } else {
                    // 英文用戶
                    $surname = $englishSurnames[array_rand($englishSurnames)];
                    $userName = $surname[0] . str_repeat('*', max(1, strlen($surname) - 1));
                }
                
                $actions = ['刊登商品', '對商品出價', '商品已結標'];
                $action = $actions[array_rand($actions)];
                $item = $itemsList[array_rand($itemsList)];
                
                $time = date('H:i', time() - rand(0, 3600));
                
                echo '<div class="activity-item">';
                if ($action === '刊登商品') {
                    echo '<div>' . htmlspecialchars($userName) . ' 委託刊登了 ' . htmlspecialchars($item) . '</div>';
                } else if ($action === '對商品出價') {
                    // 修改為10000的倍數，且不小於10000，並顯示.00小數點
                    $price = rand(1, 9000) * 10000;
                    echo '<div>' . htmlspecialchars($userName) . ' 對 ' . htmlspecialchars($item) . ' 委託出價 NT$ ' . number_format($price, 2, '.', ',') . '</div>';
                } else {
                    echo '<div>' . htmlspecialchars($userName) . ' 的 ' . htmlspecialchars($item) . ' 已結標</div>';
                }
                echo '<div class="activity-time">' . $time . '</div>';
                echo '</div>';
            }       
            ?>
        </div>
    </div>

    
    <header class="header">
        <h1>奇楠沉香交易拍賣平台</h1>
            <!-- 漢堡菜單按鈕（所有裝置通用） -->
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">☰</button>
            <!-- 下拉菜單（所有裝置通用） -->
            <div class="mobile-nav" id="mobileNav">
                <a href="index.php">首頁</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <a href="admin/dashboard.php">管理後台</a>
                    <?php else: ?>
                        <a href="customer/dashboard.php">客戶中心</a>
                    <?php endif; ?>
                    <a href="logout.php">登出</a>
                <?php else: ?>
                    <a href="login.php">登入</a>
                    <a href="register.php">註冊</a>
                <?php endif; ?>
        </div>
    </header>
    
    <div class="container">
        <div class="page-header">
            <div class="page-title">
                <h2>即時拍賣列表</h2>
            </div>
        </div>
        
        <!-- 顯示目前篩選條件 -->
        <?php if ($status_filter !== 'all' || !empty($search) || !empty($start_date) || !empty($end_date)): ?>
            <div class="filter-results">
                <strong>目前篩選條件：</strong>
                <?php 
                $filter_texts = [];
                if (!empty($search)) {
                    $filter_texts[] = "搜尋關鍵字：" . htmlspecialchars($search);
                }
                if (!empty($start_date)) {
                    $filter_texts[] = "開始日期：" . htmlspecialchars($start_date);
                }
                if (!empty($end_date)) {
                    $filter_texts[] = "結束日期：" . htmlspecialchars($end_date);
                }
                if ($status_filter !== 'all') {
                    $status_text = '';
                    switch ($status_filter) {
                        case 'pending': $status_text = '待刊登'; break;
                        case 'active': $status_text = '已刊登'; break;
                        case 'sold': $status_text = '已結標'; break;
                        case 'unsold': $status_text = '已流標'; break;
                    }
                    $filter_texts[] = "狀態：" . $status_text;
                }
                
                if (!empty($filter_texts)) {
                    echo implode('，', $filter_texts);
                } else {
                    echo "無篩選條件";
                }
                ?>
                <br>
                <small>共找到 <?php echo $total_records; ?> 筆符合條件的商品</small>
            </div>
        <?php endif; ?>
        
        <!-- 搜尋和篩選表單 -->
        <div class="filters">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="search">搜尋：</label>
                        <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="編號、商品名稱、開價金額">
                    </div>
                    
                    <div class="filter-group">
                        <label for="start_date">開始日期：</label>
                        <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="end_date">結束日期：</label>
                        <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="status">狀態篩選：</label>
                        <select id="status" name="status">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>全部狀態</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>待刊登</option>
                            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>已刊登</option>
                            <option value="sold" <?php echo $status_filter === 'sold' ? 'selected' : ''; ?>>已結標</option>
                            <option value="unsold" <?php echo $status_filter === 'unsold' ? 'selected' : ''; ?>>已流標</option>
                        </select>
                    </div>
                </div>
                
                <!-- 按鈕放在同一水平位置 -->
                <div class="buttons-container">
                    <button type="submit" class="btn">搜尋</button>
                    <a href="auction-list.php" class="btn reset-btn">重置</a>
                </div>
                
                <!-- 狀態說明（可點擊進行篩選） -->
                <div class="status-legend">
                    <div class="status-item">
                        <div class="status-pending-sample"></div>
                        <span>待刊登</span>
                    </div>
                    <div class="status-item">
                        <div class="status-active-sample"></div>
                        <span>已刊登</span>
                    </div>
                    <div class="status-item">
                        <div class="status-sold-sample"></div>
                        <span>已結標</span>
                    </div>
                    <div class="status-item">
                        <div class="status-unsold-sample"></div>
                        <span>已流標</span>
                    </div>
                </div>                                              
            </form>
        </div>
        
        <table class="auction-table">
            <thead>
                <tr>
                    <th>圖片</th>
                    <th>編號</th>
                    <th>商品名稱</th>
                    <th>開價</th>
                    <th>目前出價</th>
                    <th>預期結標金額</th>
                    <th>出價次數</th>
                    <th>日期</th>
                    <th>狀態</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr class="status-<?php echo $row['status']; ?>">
                            <td>
                                <?php if ($row['image_path']): ?>
                                    <img src="<?php echo $row['image_path']; ?>" alt="商品圖片" class="item-image">
                                <?php else: ?>
                                    <div style="width: 60px; height: 60px; background-color: #f0f0f0; display: flex; align-items: center; justify-content: center; border-radius: 4px; font-size: 12px; color: #666;">
                                        無圖片
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                            <td>
                                <a href="item-detail.php?id=<?php echo $row['id']; ?>" class="item-link">
                                    <?php echo htmlspecialchars($row['name']); ?>
                                </a>
                            </td>
                            <td><?php echo formatCurrency($row['start_price']); ?></td>
                            <td>
                                <?php if ($row['highest_bid'] && $row['highest_bid'] > 0): ?>
                                    <span class="price"><?php echo formatCurrency($row['highest_bid']); ?></span>
                                <?php else: ?>
                                    <span class="no-bids">尚未有人出價</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo formatCurrency($row['reserve_price']); ?></td>
                            <td><?php echo $row['bid_count']; ?> 次</td>
                            <td><?php echo date('Y-m-d', strtotime($row['created_at'])); ?></td>
                            <td>
                                <?php 
                                switch ($row['status']) {
                                    case 'pending': echo '待刊登'; break;
                                    case 'active': echo '已刊登'; break;
                                    case 'sold': echo '已結標'; break;
                                    case 'unsold': echo '已流標'; break;
                                    default: echo $row['status'];
                                }
                                ?>
                            </td>
                            <td>
                                <a href="item-detail.php?id=<?php echo $row['id']; ?>" class="item-link">詳細資訊</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" style="text-align: center;">目前沒有符合條件的商品</td>
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
                    <select onchange="location.href='<?php 
                        $url_params = $_GET;
                        unset($url_params['size']);
                        $base_url = http_build_query($url_params);
                        echo '?' . $base_url . ($base_url ? '&' : '') . 'size=';
                    ?>' + this.value">
                        <option value="10" <?php echo $page_size == 10 ? 'selected' : ''; ?>>10</option>
                        <option value="20" <?php echo $page_size == 20 ? 'selected' : ''; ?>>20</option>
                        <option value="30" <?php echo $page_size == 30 ? 'selected' : ''; ?>>30</option>
                    </select>
                    <span>筆</span>
                </div>
                
                <div class="pagination-links">
                    <?php if ($page > 1): ?>
                        <a href="?<?php 
                            $url_params = $_GET;
                            $url_params['page'] = $page - 1;
                            echo http_build_query($url_params);
                        ?>">&laquo; 上一頁</a>
                    <?php endif; ?>
                    
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    if ($start_page > 1): ?>
                        <a href="?<?php 
                            $url_params = $_GET;
                            $url_params['page'] = 1;
                            echo http_build_query($url_params);
                        ?>">1</a>
                        <?php if ($start_page > 2): ?>
                            <span>...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?<?php 
                                $url_params = $_GET;
                                $url_params['page'] = $i;
                                echo http_build_query($url_params);
                            ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <span>...</span>
                        <?php endif; ?>
                        <a href="?<?php 
                            $url_params = $_GET;
                            $url_params['page'] = $total_pages;
                            echo http_build_query($url_params);
                        ?>"><?php echo $total_pages; ?></a>
                    <?php endif; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?<?php 
                            $url_params = $_GET;
                            $url_params['page'] = $page + 1;
                            echo http_build_query($url_params);
                        ?>">下一頁 &raquo;</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
