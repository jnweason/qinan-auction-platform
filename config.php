<?php
/**
 * 系統設定檔
 * 包含資料庫連線資訊和全域變數設定
 */

// 資料庫設定 - 請根據 BlueHost 提供的資訊修改
define('DB_HOST', 'localhost');        // 資料庫主機
define('DB_USER', 'rqoesxmy_loan'); // 資料庫使用者名稱
define('DB_PASS', 'loan@88697'); // 資料庫密碼
define('DB_NAME', 'rqoesxmy_qinan_auction');     // 資料庫名稱

// 取得網站根目錄
$document_root = $_SERVER['DOCUMENT_ROOT'];
$base_path = str_replace('/home2/rqoesxmy/public_html', '', $document_root);
if ($base_path === '') {
    $base_path = '/';
}

// 上傳路徑設定（使用絕對路徑）
define('UPLOAD_BASE_DIR', $document_root . '/assets/uploads/');
define('ADMIN_UPLOAD_DIR', UPLOAD_BASE_DIR . 'admin-uploads/');
define('USER_UPLOAD_DIR', UPLOAD_BASE_DIR . 'user-uploads/');

// 資料庫儲存的相對路徑
define('UPLOAD_RELATIVE_DIR', '/assets/uploads/');
define('ADMIN_UPLOAD_RELATIVE_DIR', UPLOAD_RELATIVE_DIR . 'admin-uploads/');
define('USER_UPLOAD_RELATIVE_DIR', UPLOAD_RELATIVE_DIR . 'user-uploads/');

// 系統設定
define('SITE_NAME', '奇楠沉香交易拍賣平台');

// 每頁顯示筆數選項
define('PAGE_SIZES', [10, 20, 30]);

// 確保上傳目錄存在
function ensureUploadDirs() {
    $dirs = [
        UPLOAD_BASE_DIR,
        ADMIN_UPLOAD_DIR,
        USER_UPLOAD_DIR
    ];
    
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        // 確保目錄有寫入權限
        if (!is_writable($dir)) {
            chmod($dir, 0755);
        }
    }
}

// 在每次請求時確保存在目錄
ensureUploadDirs();
?>
