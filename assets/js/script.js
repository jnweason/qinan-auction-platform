/**
 * 全站通用 JavaScript 腳本
 */

// 圖片預覽功能
function setupImagePreview() {
    // 為所有縮圖添加點擊事件
    const thumbnails = document.querySelectorAll('.thumbnail');
    const mainImage = document.getElementById('mainImage');
    
    if (thumbnails.length > 0 && mainImage) {
        thumbnails.forEach(thumbnail => {
            thumbnail.addEventListener('click', function() {
                mainImage.src = this.src.replace('thumb_300_', '').replace('thumb_640_', '');
            });
        });
    }
}

// 表單驗證功能
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    
    let isValid = true;
    
    // 檢查必填欄位
    const requiredFields = form.querySelectorAll('[required]');
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.style.borderColor = '#dc3545';
            isValid = false;
        } else {
            field.style.borderColor = '#ced4da';
        }
    });
    
    // 檢查密碼確認
    const password = form.querySelector('[name="password"]');
    const confirmPassword = form.querySelector('[name="confirm_password"]');
    
    if (password && confirmPassword) {
        if (password.value !== confirmPassword.value) {
            password.style.borderColor = '#dc3545';
            confirmPassword.style.borderColor = '#dc3545';
            isValid = false;
        } else if (password.value || confirmPassword.value) {
            password.style.borderColor = '#ced4da';
            confirmPassword.style.borderColor = '#ced4da';
        }
    }
    
    return isValid;
}

// 數字格式化功能
function formatCurrency(amount) {
    return 'NT$ ' + parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// 日期格式化功能
function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.getFullYear() + '-' + 
           String(date.getMonth() + 1).padStart(2, '0') + '-' + 
           String(date.getDate()).padStart(2, '0') + ' ' +
           String(date.getHours()).padStart(2, '0') + ':' + 
           String(date.getMinutes()).padStart(2, '0') + ':' + 
           String(date.getSeconds()).padStart(2, '0');
}

// AJAX 請求功能
function ajaxRequest(url, method, data, callback) {
    const xhr = new XMLHttpRequest();
    xhr.open(method, url, true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    callback(null, response);
                } catch (e) {
                    callback('解析回應失敗', null);
                }
            } else {
                callback('請求失敗: ' + xhr.status, null);
            }
        }
    };
    
    xhr.send(JSON.stringify(data));
}

// 即時更新功能（用於拍賣價格）
function setupLiveUpdates() {
    // 這是一個示範功能，實際應用中可能需要 WebSocket 或定期 AJAX 請求
    console.log('即時更新功能已啟用');
}

// 文件載入完成後執行
document.addEventListener('DOMContentLoaded', function() {
    // 啟用圖片預覽
    setupImagePreview();
    
    // 啟用即時更新
    setupLiveUpdates();
    
    // 為所有表單添加驗證
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const formId = this.id || this.getAttribute('name');
            if (formId && !validateForm(formId)) {
                e.preventDefault();
                alert('請檢查表單欄位是否填寫正確');
            }
        });
    });
});

// 金額輸入限制
document.addEventListener('input', function(e) {
    if (e.target.type === 'number' && e.target.step) {
        const step = parseFloat(e.target.step);
        const value = parseFloat(e.target.value);
        
        if (!isNaN(value) && step > 0) {
            const rounded = Math.round(value / step) * step;
            e.target.value = rounded.toFixed(step.toString().split('.')[1]?.length || 0);
        }
    }
});

// 檔案上傳預覽
function previewFile(inputId, previewId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    
    if (input && preview && input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}

// 確認對話框
function confirmAction(message, callback) {
    if (confirm(message)) {
        if (typeof callback === 'function') {
            callback();
        }
        return true;
    }
    return false;
}

// 動態加載更多內容
function loadMoreContent(containerId, url, page) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    fetch(url + '?page=' + page)
        .then(response => response.text())
        .then(html => {
            container.innerHTML += html;
        })
        .catch(error => {
            console.error('加載更多內容時發生錯誤:', error);
        });
}

// 搜尋功能
function setupSearch(searchInputId, resultsContainerId, searchUrl) {
    const searchInput = document.getElementById(searchInputId);
    const resultsContainer = document.getElementById(resultsContainerId);
    
    if (!searchInput || !resultsContainer) return;
    
    let timeout = null;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(timeout);
        
        const query = this.value.trim();
        if (query.length < 2) {
            resultsContainer.innerHTML = '';
            return;
        }
        
        timeout = setTimeout(() => {
            fetch(searchUrl + '?q=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    displaySearchResults(data, resultsContainer);
                })
                .catch(error => {
                    console.error('搜尋時發生錯誤:', error);
                });
        }, 300);
    });
}

// 顯示搜尋結果
function displaySearchResults(results, container) {
    if (!Array.isArray(results) || results.length === 0) {
        container.innerHTML = '<div class="no-results">沒有找到相關結果</div>';
        return;
    }
    
    let html = '<div class="search-results">';
    results.forEach(item => {
        html += `
            <div class="search-result-item">
                <a href="${item.url}">${item.name}</a>
                <div class="search-result-meta">${item.description}</div>
            </div>
        `;
    });
    html += '</div>';
    
    container.innerHTML = html;
}

// 倒數計時器（用於拍賣結束時間）
function setupCountdown(endTime, elementId) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    const countdown = setInterval(() => {
        const now = new Date().getTime();
        const distance = endTime - now;
        
        if (distance < 0) {
            clearInterval(countdown);
            element.innerHTML = "拍賣已結束";
            return;
        }
        
        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
        element.innerHTML = `${days}天 ${hours}小時 ${minutes}分鐘 ${seconds}秒`;
    }, 1000);
}

// 表格排序功能
function setupTableSorting(tableId) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const headers = table.querySelectorAll('th');
    headers.forEach((header, index) => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', () => {
            sortTable(tableId, index);
        });
    });
}

// 表格排序實現
function sortTable(tableId, columnIndex) {
    const table = document.getElementById(tableId);
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    const isAscending = table.getAttribute('data-sort-order') !== 'asc';
    table.setAttribute('data-sort-order', isAscending ? 'asc' : 'desc');
    
    rows.sort((a, b) => {
        const aText = a.cells[columnIndex].textContent.trim();
        const bText = b.cells[columnIndex].textContent.trim();
        
        // 數字排序
        const aNum = parseFloat(aText.replace(/[^0-9.-]+/g, ''));
        const bNum = parseFloat(bText.replace(/[^0-9.-]+/g, ''));
        
        if (!isNaN(aNum) && !isNaN(bNum)) {
            return isAscending ? aNum - bNum : bNum - aNum;
        }
        
        // 文字排序
        return isAscending ? 
            aText.localeCompare(bText) : 
            bText.localeCompare(aText);
    });
    
    rows.forEach(row => tbody.appendChild(row));
}

// 通知功能
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    // 樣式設定
    Object.assign(notification.style, {
        position: 'fixed',
        top: '20px',
        right: '20px',
        padding: '15px 20px',
        borderRadius: '4px',
        color: 'white',
        zIndex: '1000',
        minWidth: '250px',
        boxShadow: '0 2px 10px rgba(0,0,0,0.2)'
    });
    
    // 根據類型設定背景色
    switch(type) {
        case 'success':
            notification.style.backgroundColor = '#28a745';
            break;
        case 'error':
            notification.style.backgroundColor = '#dc3545';
            break;
        case 'warning':
            notification.style.backgroundColor = '#ffc107';
            notification.style.color = '#212529';
            break;
        default:
            notification.style.backgroundColor = '#17a2b8';
    }
    
    document.body.appendChild(notification);
    
    // 3秒後自動移除
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 3000);
}

// 頁面加載指示器
function showLoadingIndicator() {
    const loading = document.createElement('div');
    loading.id = 'loading-indicator';
    loading.innerHTML = '<div class="spinner"></div><div class="loading-text">處理中...</div>';
    
    Object.assign(loading.style, {
        position: 'fixed',
        top: '50%',
        left: '50%',
        transform: 'translate(-50%, -50%)',
        backgroundColor: 'rgba(0,0,0,0.8)',
        color: 'white',
        padding: '30px',
        borderRadius: '8px',
        textAlign: 'center',
        zIndex: '9999'
    });
    
    document.body.appendChild(loading);
}

function hideLoadingIndicator() {
    const loading = document.getElementById('loading-indicator');
    if (loading) {
        loading.parentNode.removeChild(loading);
    }
}

// 匯出功能
function exportToCSV(data, filename) {
    let csv = '';
    
    // 添加標題行
    if (data.length > 0) {
        const headers = Object.keys(data[0]);
        csv += headers.join(',') + '\n';
        
        // 添加資料行
        data.forEach(row => {
            const values = headers.map(header => {
                const value = row[header] || '';
                // 處理包含逗號的值
                return typeof value === 'string' && value.includes(',') ? `"${value}"` : value;
            });
            csv += values.join(',') + '\n';
        });
    }
    
    // 建立下載連結
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// 印出功能
function printPage() {
    window.print();
}

// 複製到剪貼簿
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showNotification('已複製到剪貼簿', 'success');
        }).catch(err => {
            showNotification('複製失敗: ' + err, 'error');
        });
    } else {
        // 舊版瀏覽器支援
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        try {
            document.execCommand('copy');
            showNotification('已複 copies to clipboard', 'success');
        } catch (err) {
            showNotification('複製失敗', 'error');
        }
        document.body.removeChild(textArea);
    }
}

console.log('JavaScript 腳本已載入');
