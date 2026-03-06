<?php
// 除錯版本的註冊頁面
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>註冊頁面除錯</h1>";

// 測試資料庫連線
try {
    // 檢查必要的檔案是否存在
    if (!file_exists('includes/db_connect.php')) {
        die("錯誤：db_connect.php 檔案不存在");
    }
    
    if (!file_exists('includes/functions.php')) {
        die("錯誤：functions.php 檔案不存在");
    }
    
    // 包含檔案
    include "includes/db_connect.php";
    echo "<p>✓ 資料庫連線檔案載入成功</p>";
    
    include "includes/functions.php";
    echo "<p>✓ 功能函數檔案載入成功</p>";
    
    // 檢查資料庫連線
    if (isset($conn) && $conn) {
        echo "<p>✓ 資料庫連線成功</p>";
    } else {
        echo "<p>✗ 資料庫連線失敗</p>";
    }
    
    echo "<p>如果看到這個頁面，表示基本設定沒有問題</p>";
    echo "<p><a href='register.php'>回到註冊頁面</a></p>";
    
} catch (Exception $e) {
    echo "<p>錯誤：" . $e->getMessage() . "</p>";
}
?>
