<?php
/**
 * 登出頁面
 * 處理使用者登出功能
 */

// 啟動 Session
session_start();

// 清除所有 Session 資料
$_SESSION = array();

// 如果使用 Cookie 儲存 Session，則清除 Cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 銷毀 Session
session_destroy();

// 重新導向到首頁
header("Location: index.php");
exit();
?>
