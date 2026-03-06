# 奇楠沉香交易拍賣平台

一個完整的奇楠沉香線上拍賣交易平台，支援即時競標、商品管理、帳戶管理等功能。

## 📋 系統功能

### 前台功能
- **即時拍賣列表**：顯示所有正在拍賣的商品，類似股票交易動態
- **商品詳細資訊**：查看商品圖片、詳細資料和出價記錄
- **線上出價**：用戶可以對感興趣的商品進行出價
- **會員註冊/登入**：客戶可以註冊帳號並登入系統

### 客戶中心功能
- **儀表板**：查看帳戶餘額、商品統計等資訊
- **商品管理**：新增、編輯、刪除自己的商品
- **圖片上傳**：為商品上傳圖片，系統自動生成縮圖
- **個人資料管理**：修改密碼、查看交易記錄

### 管理員後台功能
- **儀表板**：查看平台整體統計數據
- **商品管理**：管理所有商品（新增、編輯、刪除）
- **用戶管理**：管理客戶帳號（新增、編輯、刪除）
- **圖片管理**：為商品上傳圖片
- **報表查詢**：查看日報、週報、月報等統計報表

## 🛠️ 技術架構

### 前端技術
- HTML5 + CSS3
- JavaScript (ES6+)
- 響應式設計

### 後端技術
- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx

### 資料庫結構
- `users`：用戶帳號表
- `items`：拍賣商品表
- `bids`：出價記錄表
- `images`：圖片上傳表

## 📁 專案結構

qinan-auction-platform/ 
│ 
├── config.php # 系統設定檔
├── index.php # 網站首頁 
├── login.php # 登入頁面 
├── register.php # 註冊頁面 
├── logout.php # 登出處理 
├── auction-list.php # 即時拍賣列表 
├── item-detail.php # 商品詳細資訊 
│ ├── includes/ 
│ ├── db_connect.php # 資料庫連線 
│ ├── auth_check.php # 權限驗證 
│ └── functions.php # 全域功能函數 
│ ├── admin/ # 管理員後台 
│ ├── dashboard.php # 儀表板 
│ ├── manage-items.php # 商品管理 
│ ├── add-item.php # 新增商品 
│ ├── edit-item.php # 編輯商品 
│ ├── upload-images.php # 圖片上傳 
│ ├── manage-users.php # 用戶管理 
│ ├── add-user.php # 新增用戶 
│ ├── edit-user.php # 編輯用戶 
│ └── reports.php # 報表查詢 
│ ├── customer/ # 客戶中心 
│ ├── dashboard.php # 儀表板 
│ ├── my-items.php # 我的商品 
│ ├── add-item.php # 新增商品 
│ ├── edit-item.php # 編輯商品 
│ ├── upload-images.php # 圖片上傳 
│ └── profile.php # 個人資料 
│ ├── assets/ 
│ ├── css/ 
│ │ └── style.css # CSS 樣式表
│ ├── js/ 
│ │ └── script.js # JavaScript 腳本 
│ └── uploads/ # 上傳檔案目錄 
│ ├── admin-uploads/ # 管理員上傳 
│ └── user-uploads/ # 用戶上傳 
│ └── README.md # 專案說明文件


## 🔧 安裝步驟

### 1. 環境需求
- PHP 7.4 或更高版本
- MySQL 5.7 或更高版本
- Apache 或 Nginx 網頁伺服器

### 2. 部署步驟
1. 將所有檔案上傳到網頁伺服器根目錄
2. 建立 MySQL 資料庫
3. 匯入資料庫結構（見下方 SQL 結構）
4. 修改 `config.php` 中的資料庫連線資訊
5. 設定 `assets/uploads/` 目錄權限為 755 或 777
6. 通過瀏覽器訪問網站

### 3. 資料庫結構

```sql
-- 使用者帳號表
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50),
    password VARCHAR(255),
    role ENUM('admin', 'customer'),
    balance DECIMAL(10,2) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 拍品列表
CREATE TABLE items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT,
    name VARCHAR(100),
    origin VARCHAR(100),
    weight FLOAT,
    start_price DECIMAL(10,2),
    reserve_price DECIMAL(10,2),
    current_bid DECIMAL(10,2) DEFAULT 0,
    final_price DECIMAL(10,2) DEFAULT NULL,
    status ENUM('pending','active','sold','unsold') DEFAULT 'pending',
    remarks TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id)
);

-- 出價記錄表
CREATE TABLE bids (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT,
    bidder_id INT,
    amount DECIMAL(10,2),
    bid_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(id),
    FOREIGN KEY (bidder_id) REFERENCES users(id)
);

-- 圖片上傳表
CREATE TABLE images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT,
    file_path VARCHAR(255),
    FOREIGN KEY (item_id) REFERENCES items(id)
);
