<?php
/**
 * 設定上傳目錄
 */

echo "<h1>上傳目錄設定工具</h1>";

// 設定目錄
$directories = [
    $_SERVER['DOCUMENT_ROOT'] . '/assets/',
    $_SERVER['DOCUMENT_ROOT'] . '/assets/uploads/',
    $_SERVER['DOCUMENT_ROOT'] . '/assets/uploads/admin-uploads/',
    $_SERVER['DOCUMENT_ROOT'] . '/assets/uploads/user-uploads/'
];

echo "<h2>建立目錄</h2>";

foreach ($directories as $dir) {
    echo "<p>處理目錄: $dir</p>";
    
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "<p style='color:green'>✓ 目錄建立成功</p>";
        } else {
            echo "<p style='color:red'>✗ 目錄建立失敗</p>";
        }
    } else {
        echo "<p style='color:blue'>✓ 目錄已存在</p>";
    }
    
    if (is_writable($dir)) {
        echo "<p style='color:green'>✓ 目錄可寫入</p>";
    } else {
        echo "<p style='color:orange'>⚠ 目錄不可寫入，嘗試設定權限...</p>";
        if (chmod($dir, 0755)) {
            echo "<p style='color:green'>✓ 權限設定成功</p>";
        } else {
            echo "<p style='color:red'>✗ 權限設定失敗</p>";
        }
    }
    
    echo "<hr>";
}

echo "<h2>建立測試檔案</h2>";

// 建立簡單的測試圖片
$test_dir = $_SERVER['DOCUMENT_ROOT'] . '/assets/uploads/admin-uploads/';
if (is_writable($test_dir)) {
    // 建立簡單的文字檔案作為測試
    $test_file = $test_dir . 'test.txt';
    if (file_put_contents($test_file, 'Test file created at: ' . date('Y-m-d H:i:s'))) {
        echo "<p style='color:green'>✓ 測試檔案建立成功</p>";
        echo "<p>測試檔案路徑: $test_file</p>";
        echo "<p>測試檔案內容: " . file_get_contents($test_file) . "</p>";
    } else {
        echo "<p style='color:red'>✗ 測試檔案建立失敗</p>";
    }
} else {
    echo "<p style='color:red'>✗ 測試目錄不可寫入</p>";
}

echo "<h2>完成！</h2>";
echo "<p>請重新測試圖片上傳功能。</p>";
echo "<p><a href='test-image-display.php'>點此測試圖片顯示</a></p>";
?>
