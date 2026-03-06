<?php
/**
 * 圖片顯示測試
 */

echo "<h1>圖片顯示測試</h1>";

// 測試建立一些簡單的測試圖片
$test_dir = $_SERVER['DOCUMENT_ROOT'] . '/assets/uploads/admin-uploads/';

if (!is_dir($test_dir)) {
    mkdir($test_dir, 0755, true);
}

// 建立簡單的測試圖片（使用 GD 庫）
if (extension_loaded('gd')) {
    // 建立一個簡單的測試圖片
    $test_image = imagecreate(200, 200);
    $background_color = imagecolorallocate($test_image, 255, 255, 255);
    $text_color = imagecolorallocate($test_image, 0, 0, 0);
    
    imagestring($test_image, 5, 30, 90, 'Test Image', $text_color);
    
    $test_image_path = $test_dir . 'test-display.jpg';
    imagejpeg($test_image, $test_image_path);
    imagedestroy($test_image);
    
    echo "<h2>測試圖片已建立</h2>";
    echo "<p>圖片路徑: " . str_replace($_SERVER['DOCUMENT_ROOT'], '', $test_image_path) . "</p>";
    echo "<img src='/assets/uploads/admin-uploads/test-display.jpg' alt='Test Image' style='border: 1px solid #ccc;'>";
} else {
    echo "<p style='color:red'>GD 庫未啟用，無法建立測試圖片</p>";
}

echo "<h2>目錄內容檢查</h2>";

if (is_dir($test_dir)) {
    $files = scandir($test_dir);
    echo "<p>目錄內容:</p>";
    echo "<ul>";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "<li>$file</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p style='color:red'>目錄不存在: $test_dir</p>";
}

echo "<h2>權限檢查</h2>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>測試目錄: $test_dir</p>";
echo "<p>目錄是否存在: " . (is_dir($test_dir) ? '是' : '否') . "</p>";
echo "<p>目錄是否可讀: " . (is_readable($test_dir) ? '是' : '否') . "</p>";
echo "<p>目錄是否可寫: " . (is_writable($test_dir) ? '是' : '否') . "</p>";
?>
