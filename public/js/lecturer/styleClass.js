/**
 * File JavaScript cho trang Danh sách lớp học phần
 * Tìm kiếm client-side bổ sung
 */

document.addEventListener('DOMContentLoaded', function() {
    initSearchFunctionality();
    initKeyboardShortcuts();
});

/**
 * Khởi tạo chức năng tìm kiếm client-side
 */
function initSearchFunctionality() {
    const searchInput = document.getElementById('search-class-input');
    if (!searchInput) return;
    
    // Tìm kiếm real-time (chỉ filter client-side)
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        const rows = document.querySelectorAll('#full-class-table-body tr');
        
        // Loại bỏ hàng thông báo "không có dữ liệu" nếu có
        const noDataRow = document.querySelector('.no-data');
        if (noDataRow && searchTerm) {
            noDataRow.style.display = 'none';
        }
        
        let visibleCount = 0;
        
        rows.forEach(row => {
            // Bỏ qua hàng thông báo
            if (row.classList.contains('no-data')) {
                return;
            }
            
            const cells = row.querySelectorAll('td');
            let rowText = '';
            
            // Lấy text từ 4 cột đầu (Mã lớp, Mã môn, Tên môn, Số SV)
            for (let i = 0; i < 4; i++) {
                if (cells[i]) {
                    rowText += cells[i].textContent.toLowerCase() + ' ';
                }
            }
            
            if (searchTerm === '' || rowText.includes(searchTerm)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Nếu không có kết quả nào và có từ khóa tìm kiếm
        if (visibleCount === 0 && searchTerm) {
            showNoResultsMessage(searchTerm);
        } else {
            removeNoResultsMessage();
        }
    });
}

/**
 * Hiển thị thông báo không tìm thấy kết quả (client-side)
 */
function showNoResultsMessage(searchTerm) {
    // Kiểm tra xem đã có thông báo chưa
    if (document.querySelector('.no-results-message')) {
        return;
    }
    
    const tbody = document.getElementById('full-class-table-body');
    if (!tbody) return;
    
    const messageRow = document.createElement('tr');
    messageRow.className = 'no-results-message';
    messageRow.innerHTML = `
        <td colspan="6" class="text-center">
            <div style="padding: 40px 20px; color: #6c757d;">
                <i class="fas fa-search fa-2x" style="margin-bottom: 15px; color: #dee2e6;"></i>
                <p style="margin: 0 0 10px; font-size: 16px; font-weight: 500;">
                    Không tìm thấy kết quả cho "<strong>${searchTerm}</strong>"
                </p>
                <p style="margin: 0; font-size: 14px;">Thử lại với từ khóa khác</p>
            </div>
        </td>
    `;
    
    tbody.appendChild(messageRow);
}

/**
 * Xóa thông báo không tìm thấy kết quả
 */
function removeNoResultsMessage() {
    const message = document.querySelector('.no-results-message');
    if (message) {
        message.remove();
    }
}

/**
 * Khởi tạo phím tắt
 */
function initKeyboardShortcuts() {
    const searchInput = document.getElementById('search-class-input');
    
    // Ctrl/Cmd + F để focus vào ô tìm kiếm
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            e.preventDefault();
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }
        
        // Esc để xóa tìm kiếm client-side
        if (e.key === 'Escape' && document.activeElement === searchInput) {
            e.preventDefault();
            if (searchInput.value) {
                searchInput.value = '';
                // Kích hoạt sự kiện input để reset filter
                const event = new Event('input', { bubbles: true });
                searchInput.dispatchEvent(event);
            }
        }
    });
}

console.log('Class List JavaScript loaded successfully.');