<?php
/**
 * 全域功能函數檔案
 * 包含常用的輔助函數
 */

/**
 * 產生分頁連結
 * @param int $total_records 總記錄數
 * @param int $current_page 目前頁碼
 * @param int $records_per_page 每頁記錄數
 * @param string $base_url 基本 URL
 * @return string 分頁 HTML
 */
function generatePagination($total_records, $current_page, $records_per_page, $base_url) {
    $total_pages = ceil($total_records / $records_per_page);
    
    if ($total_pages <= 1) {
        return '';
    }
    
    $pagination = '<div class="pagination">';
    
    // 上一頁
    if ($current_page > 1) {
        $prev_page = $current_page - 1;
        $pagination .= "<a href='{$base_url}&page={$prev_page}'>&laquo; 上一頁</a>";
    }
    
    // 頁碼
    for ($i = 1; $i <= $total_pages; $i++) {
        if ($i == $current_page) {
            $pagination .= "<span class='current'>{$i}</span>";
        } else {
            $pagination .= "<a href='{$base_url}&page={$i}'>{$i}</a>";
        }
    }
    
    // 下一頁
    if ($current_page < $total_pages) {
        $next_page = $current_page + 1;
        $pagination .= "<a href='{$base_url}&page={$next_page}'>下一頁 &raquo;</a>";
    }
    
    $pagination .= '</div>';
    
    return $pagination;
}

/**
 * 格式化金額
 * @param float $amount 金額
 * @return string 格式化後的金額
 */
function formatCurrency($amount) {
    return 'NT$ ' . number_format($amount, 2, '.', ',');
}

/**
 * 格式化日期時間
 * @param string $datetime 日期時間
 * @return string 格式化後的日期時間
 */
function formatDateTime($datetime) {
    return date('Y-m-d H:i:s', strtotime($datetime));
}

/**
 * 產生隨機字串
 * @param int $length 字串長度
 * @return string 隨機字串
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $randomString;
}

/**
 * 檢查是否為有效的電子郵件格式
 * @param string $email 電子郵件
 * @return bool 是否有效
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * 安全輸出文字（防止 XSS）
 * @param string $text 要輸出的文字
 * @return string 安全的文字
 */
function safeOutput($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * 修正版圖片處理函數
 */

/**
 * 調整圖片大小並儲存（BlueHost 相容版）
 * @param string $source_path 原始圖片路徑（完整路徑）
 * @param string $destination_path 目標圖片路徑（完整路徑）
 * @param int $width 目標寬度
 * @param int $height 目標高度
 * @return bool 是否成功
 */
function resizeImage($source_path, $destination_path, $width, $height) {
    // 檢查原始檔案是否存在
    if (!file_exists($source_path)) {
        error_log("原始圖片不存在: " . $source_path);
        return false;
    }
    
    // 取得圖片資訊
    $image_info = @getimagesize($source_path);
    if (!$image_info) {
        error_log("無法讀取圖片資訊: " . $source_path);
        return false;
    }
    
    $mime = $image_info['mime'];
    
    // 根據 MIME 類型建立圖片資源
    $image = null;
    switch ($mime) {
        case 'image/jpeg':
            $image = @imagecreatefromjpeg($source_path);
            break;
        case 'image/png':
            $image = @imagecreatefrompng($source_path);
            break;
        case 'image/gif':
            $image = @imagecreatefromgif($source_path);
            break;
        default:
            error_log("不支援的圖片格式: " . $mime);
            return false;
    }
    
    if (!$image) {
        error_log("無法建立圖片資源: " . $source_path);
        return false;
    }
    
    // 取得原始圖片尺寸
    $orig_width = imagesx($image);
    $orig_height = imagesy($image);
    
    // 建立新的畫布
    $new_image = imagecreatetruecolor($width, $height);
    
    // 處理透明背景
    if ($mime == 'image/png' || $mime == 'image/gif') {
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
        $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
        imagefilledrectangle($new_image, 0, 0, $width, $height, $transparent);
    } else {
        // 白色背景
        $white = imagecolorallocate($new_image, 255, 255, 255);
        imagefilledrectangle($new_image, 0, 0, $width, $height, $white);
    }
    
    // 調整圖片大小
    imagecopyresampled($new_image, $image, 0, 0, 0, 0, $width, $height, $orig_width, $orig_height);
    
    // 確保目標目錄存在
    $dest_dir = dirname($destination_path);
    if (!is_dir($dest_dir)) {
        mkdir($dest_dir, 0755, true);
    }
    
    // 儲存圖片
    $result = false;
    switch ($mime) {
        case 'image/jpeg':
            $result = @imagejpeg($new_image, $destination_path, 85);
            break;
        case 'image/png':
            $result = @imagepng($new_image, $destination_path, 6);
            break;
        case 'image/gif':
            $result = @imagegif($new_image, $destination_path);
            break;
    }
    
    // 釋放記憶體
    imagedestroy($image);
    imagedestroy($new_image);
    
    if (!$result) {
        error_log("圖片儲存失敗: " . $destination_path);
    }
    
    return $result;
}

/**
 * 上傳圖片並生成縮圖（BlueHost 相容版）
 * @param array $uploaded_file 上傳的檔案資訊
 * @param string $upload_relative_dir 相對上傳目錄（如 /assets/uploads/admin-uploads/）
 * @param int $item_id 商品ID
 * @return array 包含結果資訊的陣列
 */
function uploadAndProcessImage($uploaded_file, $upload_relative_dir, $item_id) {
    $result = [
        'success' => false,
        'message' => '',
        'file_path' => ''
    ];
    
    // 檢查上傳錯誤
    if ($uploaded_file['error'] !== UPLOAD_ERR_OK) {
        $result['message'] = '檔案上傳失敗，錯誤代碼: ' . $uploaded_file['error'];
        return $result;
    }
    
    // 檢查檔案大小（限制 10MB）
    if ($uploaded_file['size'] > 10 * 1024 * 1024) {
        $result['message'] = '檔案太大，請上傳小於 10MB 的圖片';
        return $result;
    }
    
    // 檢查檔案類型
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $file_type = mime_content_type($uploaded_file['tmp_name']);
    
    if (!in_array($file_type, $allowed_types)) {
        $result['message'] = '不支援的圖片格式，僅支援 JPG、PNG、GIF';
        return $result;
    }
    
    // 產生唯一檔名
    $file_extension = strtolower(pathinfo($uploaded_file['name'], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '_' . time() . '.' . $file_extension;
    
    // 完整的檔案路徑（BlueHost 實際路徑）
    $full_upload_dir = $_SERVER['DOCUMENT_ROOT'] . $upload_relative_dir;
    $original_path = $full_upload_dir . $new_filename;
    
    // 確保上傳目錄存在
    if (!is_dir($full_upload_dir)) {
        if (!mkdir($full_upload_dir, 0755, true)) {
            $result['message'] = '無法建立上傳目錄: ' . $full_upload_dir;
            return $result;
        }
    }
    
    // 移動上傳的檔案
    if (move_uploaded_file($uploaded_file['tmp_name'], $original_path)) {
        // 生成縮圖
        $thumb_300_path = $full_upload_dir . 'thumb_300_' . $new_filename;
        $thumb_640_path = $full_upload_dir . 'thumb_640_' . $new_filename;
        
        // 生成 300x300 縮圖
        $thumb_300_success = resizeImage($original_path, $thumb_300_path, 300, 300);
        
        // 生成 640x640 縮圖
        $thumb_640_success = resizeImage($original_path, $thumb_640_path, 640, 640);
        
        if ($thumb_300_success) {
            // 儲存到資料庫的路徑（相對於網站根目錄）
            $db_file_path = $upload_relative_dir . 'thumb_300_' . $new_filename;
            
            $result['success'] = true;
            $result['message'] = '圖片上傳成功';
            $result['file_path'] = $db_file_path;
        } else {
            $result['message'] = '縮圖生成失敗';
            // 刪除已上傳的原始檔案
            if (file_exists($original_path)) {
                unlink($original_path);
            }
        }
    } else {
        $result['message'] = '檔案儲存失敗，無法移動到: ' . $original_path;
        error_log('上傳失敗詳細資訊: ' . print_r(error_get_last(), true));
    }
    
    return $result;
}

/**
 * 刪除圖片檔案
 * @param string $file_path 資料庫中的檔案路徑
 */
function deleteImageFiles($file_path) {
    $full_path = $_SERVER['DOCUMENT_ROOT'] . $file_path;
    $dir = dirname($full_path);
    $filename = basename($file_path, '.' . pathinfo($file_path, PATHINFO_EXTENSION));
    $extension = pathinfo($file_path, PATHINFO_EXTENSION);
    
    // 刪除所有相關的圖片檔案
    $files_to_delete = [
        $full_path, // 300x300 縮圖
        $dir . '/thumb_640_' . $filename . '.' . $extension, // 640x640 縮圖
        $dir . '/' . $filename . '.' . $extension // 原始檔案
    ];
    
    foreach ($files_to_delete as $delete_file) {
        if (file_exists($delete_file)) {
            unlink($delete_file);
        }
    }
}

/**
 * 測試圖片上傳功能
 */
function testImageUpload() {
    echo "<h2>圖片上傳測試</h2>";
    echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
    echo "<p>上傳目錄測試:</p>";
    
    $test_dirs = [
        $_SERVER['DOCUMENT_ROOT'] . '/assets/uploads/',
        $_SERVER['DOCUMENT_ROOT'] . '/assets/uploads/admin-uploads/',
        $_SERVER['DOCUMENT_ROOT'] . '/assets/uploads/user-uploads/'
    ];
    
    foreach ($test_dirs as $dir) {
        echo "<p>目錄: $dir</p>";
        if (!is_dir($dir)) {
            if (mkdir($dir, 0755, true)) {
                echo "<p style='color:green'>目錄建立成功</p>";
            } else {
                echo "<p style='color:red'>目錄建立失敗</p>";
            }
        } else {
            echo "<p style='color:blue'>目錄已存在</p>";
        }
        
        if (is_writable($dir)) {
            echo "<p style='color:green'>目錄可寫入</p>";
        } else {
            echo "<p style='color:red'>目錄不可寫入</p>";
            if (chmod($dir, 0755)) {
                echo "<p style='color:green'>權限設定成功</p>";
            } else {
                echo "<p style='color:red'>權限設定失敗</p>";
            }
        }
        echo "<hr>";
    }
}

/**
 * 驗證手機號碼格式
 * @param string $phone 手機號碼
 * @return bool 是否有效
 */
function isValidPhone($phone) {
    return preg_match('/^09\d{8}$/', $phone) === 1;
}

?>
