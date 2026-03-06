<?php
/**
 * 圖片上傳頁面
 * 管理員可以為商品上傳圖片並自動生成縮圖
 */

// 引入必要檔案
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// 檢查登入和權限
checkLogin();
if (!isAdmin()) {
    header("Location: ../index.php");
    exit();
}

// 取得商品 ID
$item_id = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;

// 檢查商品 ID 是否有效
if ($item_id <= 0) {
    header("Location: manage-items.php");
    exit();
}

// 查詢商品資訊
$sql = "SELECT * FROM items WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();

// 檢查商品是否存在
if ($result->num_rows == 0) {
    header("Location: manage-items.php");
    exit();
}

$item = $result->fetch_assoc();

// 初始化變數
$error_message = '';
$success_message = '';

// 處理圖片上傳
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['images'])) {
    $uploaded_files = $_FILES['images'];
    
    // 檢查是否有檔案被上傳
    if (!isset($uploaded_files['name'][0]) || empty($uploaded_files['name'][0])) {
        $error_message = '請選擇至少一個圖片檔案';
    } else {
        try {
            $upload_success_count = 0;
            $upload_errors = [];
            
            // 處理每個上傳的檔案
            for ($i = 0; $i < count($uploaded_files['name']); $i++) {
                // 檢查是否有錯誤
                if ($uploaded_files['error'][$i] !== UPLOAD_ERR_OK) {
                    $upload_errors[] = "檔案 {$uploaded_files['name'][$i]} 上傳失敗";
                    continue;
                }
                
                // 檢查檔案類型
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $file_type = mime_content_type($uploaded_files['tmp_name'][$i]);
                
                if (!in_array($file_type, $allowed_types)) {
                    $upload_errors[] = "檔案 {$uploaded_files['name'][$i]} 不是有效的圖片格式";
                    continue;
                }
                
                // 產生唯一的檔案名稱
                $file_extension = pathinfo($uploaded_files['name'][$i], PATHINFO_EXTENSION);
                $new_filename = uniqid() . '_' . time() . '.' . $file_extension;
                $upload_path = ADMIN_UPLOAD_DIR . $new_filename;
                
                // 移動上傳的檔案
                if (move_uploaded_file($uploaded_files['tmp_name'][$i], $upload_path)) {
                    // 生成縮圖
                    $thumb_300_path = ADMIN_UPLOAD_DIR . 'thumb_300_' . $new_filename;
                    $thumb_640_path = ADMIN_UPLOAD_DIR . 'thumb_640_' . $new_filename;
                    
                    if (resizeImage($upload_path, $thumb_300_path, 300, 300) &&
                        resizeImage($upload_path, $thumb_640_path, 640, 640)) {
                        
                        // 插入資料庫記錄
                        $insert_sql = "INSERT INTO images (item_id, file_path) VALUES (?, ?)";
                        $stmt = $conn->prepare($insert_sql);
                        $relative_path = '/assets/uploads/admin-uploads/thumb_300_' . $new_filename;
                        $stmt->bind_param("is", $item_id, $relative_path);
                        $stmt->execute();
                        
                        $upload_success_count++;
                    } else {
                        $upload_errors[] = "檔案 {$uploaded_files['name'][$i]} 縮圖生成失敗";
                        // 刪除已上傳的檔案
                        unlink($upload_path);
                    }
                } else {
                    $upload_errors[] = "檔案 {$uploaded_files['name'][$i]} 儲存失敗";
                }
            }
            
            if ($upload_success_count > 0) {
                $success_message = "成功上傳 {$upload_success_count} 個圖片檔案！";
            }
            
            if (!empty($upload_errors)) {
                $error_message = implode('<br>', $upload_errors);
            }
            
        } catch (Exception $e) {
            $error_message = '圖片上傳時發生錯誤：' . $e->getMessage();
        }
    }
}

// 查詢已上傳的圖片
$image_sql = "SELECT * FROM images WHERE item_id = ? ORDER BY id ASC";
$stmt = $conn->prepare($image_sql);
$stmt->bind_param("i", $item_id);
$stmt->execute();
$images_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>圖片上傳 - <?php echo SITE_NAME; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        
        .header {
            background-color: #343a40;
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            margin: 0;
            font-size: 1.5rem;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 15px;
            padding: 8px 12px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .nav-links a:hover {
            background-color: #495057;
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .page-header {
            margin-bottom: 2rem;
        }
        
        .page-header h2 {
            color: #343a40;
            margin: 0;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 1rem;
            color: #007cba;
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .upload-section {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .upload-section h3 {
            color: #343a40;
            margin-top: 0;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #343a40;
        }
        
        .file-input-wrapper {
            position: relative;
            display: inline-block;
            overflow: hidden;
            background-color: #007cba;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .file-input-wrapper:hover {
            background-color: #0056b3;
        }
        
        .file-input-wrapper input[type="file"] {
            position: absolute;
            top: 0;
            right: 0;
            margin: 0;
            padding: 0;
            font-size: 20px;
            cursor: pointer;
            opacity: 0;
            filter: alpha(opacity=0);
            height: 100%;
        }
        
        .file-name {
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .btn {
            background-color: #28a745;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #218838;
        }
        
        .btn:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }
        
        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        
        .error {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }
        
        .success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }
        
        .images-section {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .images-section h3 {
            color: #343a40;
            margin-top: 0;
        }
        
        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .image-card {
            border: 1px solid #dee2e6;
            border-radius: 4px;
            overflow: hidden;
            background-color: #f8f9fa;
        }
        
        .image-preview {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        
        .image-info {
            padding: 0.5rem;
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .delete-link {
            color: #dc3545;
            text-decoration: none;
            font-size: 0.8rem;
        }
        
        .delete-link:hover {
            text-decoration: underline;
        }
        
        .no-images {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 2rem;
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 1rem;
            }
            
            .upload-section,
            .images-section {
                padding: 1rem;
            }
            
            .image-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <h1><?php echo SITE_NAME; ?> - 圖片上傳</h1>
        <div class="nav-links">
            <a href="dashboard.php">儀表板</a>
            <a href="manage-items.php">商品管理</a>
            <a href="../index.php">返回首頁</a>
            <a href="../logout.php">登出</a>
        </div>
    </header>
    
    <div class="container">
        <a href="manage-items.php" class="back-link">&larr; 返回商品管理</a>
        
        <div class="page-header">
            <h2>圖片上傳 - <?php echo safeOutput($item['name']); ?></h2>
        </div>
        
        <?php if ($error_message): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <div class="upload-section">
            <h3>上傳新圖片</h3>
            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="form-group">
                    <label for="images">選擇圖片檔案（可多選）：</label>
                    <div class="file-input-wrapper">
                        選擇檔案
                        <input type="file" id="images" name="images[]" multiple accept="image/*" onchange="updateFileName()">
                    </div>
                    <div class="file-name" id="fileName">尚未選擇檔案</div>
                </div>
                
                <button type="submit" class="btn">上傳圖片</button>
            </form>
        </div>
        
        <div class="images-section">
            <h3>已上傳圖片</h3>
            <?php if ($images_result->num_rows > 0): ?>
                <div class="image-grid">
                    <?php while ($image = $images_result->fetch_assoc()): ?>
                        <div class="image-card">
                            <img src="<?php echo $image['file_path']; ?>" alt="商品圖片" class="image-preview">
                            <div class="image-info">
                                <a href="?delete_image=<?php echo $image['id']; ?>&item_id=<?php echo $item_id; ?>" 
                                   class="delete-link" 
                                   onclick="return confirm('確定要刪除此圖片嗎？')">
                                    刪除
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-images">目前沒有上傳任何圖片</div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function updateFileName() {
            const fileInput = document.getElementById('images');
            const fileNameDiv = document.getElementById('fileName');
            
            if (fileInput.files.length > 0) {
                const fileNames = Array.from(fileInput.files).map(file => file.name).join(', ');
                fileNameDiv.textContent = `已選擇 ${fileInput.files.length} 個檔案: ${fileNames}`;
            } else {
                fileNameDiv.textContent = '尚未選擇檔案';
            }
        }
        
        // 處理圖片刪除
        <?php if (isset($_GET['delete_image'])): ?>
        window.addEventListener('load', function() {
            <?php
            $delete_image_id = intval($_GET['delete_image']);
            try {
                // 查詢圖片路徑
                $image_sql = "SELECT file_path FROM images WHERE id = ?";
                $stmt = $conn->prepare($image_sql);
                $stmt->bind_param("i", $delete_image_id);
                $stmt->execute();
                $image_result = $stmt->get_result();
                
                if ($image_result->num_rows > 0) {
                    $image = $image_result->fetch_assoc();
                    $file_path = $_SERVER['DOCUMENT_ROOT'] . $image['file_path'];
                    $dir = pathinfo($file_path, PATHINFO_DIRNAME);
                    $filename = pathinfo($file_path, PATHINFO_FILENAME);
                    $extension = pathinfo($file_path, PATHINFO_EXTENSION);
                    
                    // 刪除原圖和縮圖
                    $files_to_delete = [
                        $file_path,
                        $dir . '/thumb_300_' . $filename . '.' . $extension,
                        $dir . '/thumb_640_' . $filename . '.' . $extension
                    ];
                    
                    foreach ($files_to_delete as $delete_file) {
                        if (file_exists($delete_file)) {
                            unlink($delete_file);
                        }
                    }
                    
                    // 從資料庫刪除記錄
                    $delete_sql = "DELETE FROM images WHERE id = ?";
                    $stmt = $conn->prepare($delete_sql);
                    $stmt->bind_param("i", $delete_image_id);
                    $stmt->execute();
                    
                    echo "alert('圖片刪除成功！');";
                }
            } catch (Exception $e) {
                echo "alert('刪除圖片時發生錯誤：" . $e->getMessage() . "');";
            }
            ?>
            window.location.href = '?item_id=<?php echo $item_id; ?>';
        });
        <?php endif; ?>
    </script>
</body>
</html>
