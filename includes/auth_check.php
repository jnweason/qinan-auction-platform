<?php
/**
 * 權限驗證檔案
 * 負責檢查使用者登入狀態和角色權限
 */

// 啟動 Session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * 檢查使用者是否已登入
 * 如果未登入則重新導向到登入頁面
 */
function checkLogin() {
    // 檢查 Session 中是否有使用者 ID
    if (!isset($_SESSION['user_id'])) {
        // 未登入，重新導向到登入頁面
        header("Location: login.php");
        exit();
    }
}

/**
 * 檢查使用者是否為管理員
 * @return bool 是否為管理員
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * 檢查使用者是否為客戶
 * @return bool 是否為客戶
 */
function isCustomer() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'customer';
}

/**
 * 取得目前登入使用者的 ID
 * @return int|null 使用者 ID
 */
function getCurrentUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

/**
 * 取得目前登入使用者的角色
 * @return string|null 使用者角色
 */
function getCurrentUserRole() {
    return isset($_SESSION['role']) ? $_SESSION['role'] : null;
}

/**
 * 登出功能
 */
function logout() {
    // 清除所有 Session 資料
    session_destroy();
    
    // 重新導向到首頁或登入頁面
    header("Location: login.php");
    exit();
}

/**
 * 檢查是否為特定使用者擁有資料
 * @param int $owner_id 資料擁有者的使用者 ID
 * @return bool 是否有權限
 */
function isOwner($owner_id) {
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] == $owner_id;
}
?>
