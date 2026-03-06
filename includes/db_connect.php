<?php
/**
 * 資料庫連線檔案
 * 負責建立與資料庫的連線
 */

// 引入設定檔
include_once __DIR__ . '/../config.php';

// 建立資料庫連線
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // 檢查連線是否成功
    if ($conn->connect_error) {
        throw new Exception("資料庫連接失敗: " . $conn->connect_error);
    }
    
    // 設定字元集為 UTF-8
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    die("連線錯誤: " . $e->getMessage());
}

/**
 * 執行 SQL 查詢並回傳結果
 * @param string $sql SQL 查詢語句
 * @param array $params 參數陣列
 * @return mysqli_result|bool 查詢結果
 */
function executeQuery($sql, $params = []) {
    global $conn;
    
    // 準備 statement
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("SQL 準備失敗: " . $conn->error);
    }
    
    // 如果有參數，綁定參數
    if (!empty($params)) {
        $types = str_repeat('s', count($params)); // 假設都是字串，實際使用時可根據需要調整
        $stmt->bind_param($types, ...$params);
    }
    
    // 執行查詢
    if (!$stmt->execute()) {
        throw new Exception("查詢執行失敗: " . $stmt->error);
    }
    
    // 回傳結果
    return $stmt->get_result();
}

/**
 * 執行 INSERT、UPDATE、DELETE 等操作
 * @param string $sql SQL 語句
 * @param array $params 參數陣列
 * @return int 影響的列數
 */
function executeNonQuery($sql, $params = []) {
    global $conn;
    
    // 準備 statement
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("SQL 準備失敗: " . $conn->error);
    }
    
    // 如果有參數，綁定參數
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    
    // 執行查詢
    if (!$stmt->execute()) {
        throw new Exception("查詢執行失敗: " . $stmt->error);
    }
    
    // 回傳影響的列數
    return $stmt->affected_rows;
}
?>
