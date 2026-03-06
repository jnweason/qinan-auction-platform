<?php
/**
 * 圖片顯示測試頁面
 */

// 測試圖片路徑
$test_paths = [
    '/assets/uploads/admin-uploads/test.jpg',
    '/assets/uploads/admin-uploads/thumb_300_test.jpg',
    '/assets/uploads/admin-uploads/thumb_640_test.jpg'
];

echo "<h1>圖片顯示測試</h1>";

foreach ($test_paths as $path) {
    $full_path = $_SERVER['DOCUMENT_ROOT'] . $path;
    echo "<h3>測試路徑: $path</h3>";
    
    if (file_exists($full_path)) {
        echo "<p style='color:green'>檔案存在</p>";
        echo "<img src='$path' style='max-width:300px; border:1px solid #ccc;'><br><br>";
    } else {
        echo "<p style='color:red'>檔案不存在: $full_path</p>";
    }
    
    echo "<hr>";
}
?>
