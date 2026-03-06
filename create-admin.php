<?php
/**
 * 創建管理員帳號腳本
 * 僅供緊急情況下使用
 */

// 注意：在生產環境中請刪除或保護此檔案

// 引入資料庫連線
include "includes/db_connect.php";

// 設定管理員帳號資訊
$admin_username = 'admin';
$admin_password = 'admin123'; // 請修改為安全密碼

// 加密密碼
$hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

try {
    // 插入管理員帳號
    $sql = "INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $admin_username, $hashed_password);
    
    if ($stmt->execute()) {
        echo "管理員帳號創建成功！<br>";
        echo "帳號：$admin_username<br>";
        echo "密碼：$admin_password<br>";
        echo "<br>請立即登入並修改密碼！<br>";
        echo "<a href='login.php'>點此登入</a>";
    } else {
        echo "創建失敗：" . $stmt->error;
    }
} catch (Exception $e) {
    echo "錯誤：" . $e->getMessage();
}

// 重要提醒：使用後請立即刪除或保護此檔案
echo "<br><br><strong style='color: red;'>警告：請立即刪除 create-admin.php 檔案！</strong>";
?>
