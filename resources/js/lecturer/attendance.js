// Trang3.js - Hiển thị danh sách sinh viên đơn giản
function renderStudentList() {
    // Lấy dữ liệu từ data3.js
    const students = window.mockStudents || [];
    const tableBody = document.getElementById('attendance-table-body');
    
    if (!tableBody) {
        console.error('Không tìm thấy element attendance-table-body');
        return;
    }
    
    // Xóa nội dung cũ
    tableBody.innerHTML = '';
    
    // Render từng sinh viên
    students.forEach((student, index) => {
        // Tạo HTML cho mỗi hàng
        let statusBtnClass = '';
        let statusText = '';
        
        switch(student.status) {
            case 'present':
                statusBtnClass = 'active';
                statusText = 'Có mặt';
                break;
            case 'excused':
                statusBtnClass = 'active';
                statusText = 'Vắng có phép';
                break;
            case 'absent':
                statusBtnClass = 'active';
                statusText = 'Vắng';
                break;
        }
        
        const rowHTML = `
            <div class="table-row">
                <div>${index + 1}</div>
                <div>${student.name}</div>
                <div>${student.studentCode}</div>
                <div class="status-buttons">
                    <button class="status-btn ${student.status === 'present' ? 'active' : ''}" 
                            data-status="present">
                        Có mặt
                    </button>
                    <button class="status-btn ${student.status === 'excused' ? 'active' : ''}" 
                            data-status="excused">
                        Vắng có phép
                    </button>
                    <button class="status-btn ${student.status === 'absent' ? 'active' : ''}" 
                            data-status="absent">
                        Vắng
                    </button>
                </div>
            </div>
        `;
        
        tableBody.innerHTML += rowHTML;
    });
    
    // Tính toán thống kê
    updateStats(students);
}

function updateStats(students) {
    const stats = {
        present: students.filter(s => s.status === 'present').length,
        absent: students.filter(s => s.status === 'absent').length,
        excused: students.filter(s => s.status === 'excused').length,
        total: students.length
    };
    
    // Cập nhật thống kê
    document.querySelector('[data-count="present"]').textContent = stats.present;
    document.querySelector('[data-count="absent"]').textContent = stats.absent;
    document.querySelector('[data-count="excused"]').textContent = stats.excused;
    document.querySelector('[data-count="total"]').textContent = stats.total;
}

// Thêm event listeners đơn giản
function setupEventListeners() {
    // Xử lý khi click vào nút trạng thái
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('status-btn')) {
            // Lấy hàng chứa nút được click
            const row = e.target.closest('.table-row');
            const statusButtons = row.querySelectorAll('.status-btn');
            
            // Xóa class active từ tất cả các nút trong hàng
            statusButtons.forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Thêm class active cho nút được click
            e.target.classList.add('active');
            
            // Cập nhật thống kê
            updateAllStats();
        }
        
        // Xử lý nút lưu
        if (e.target.id === 'save-attendance-btn' || e.target.closest('#save-attendance-btn')) {
            saveAttendance();
        }
    });
}

function updateAllStats() {
    // Đếm lại từ danh sách hiển thị
    const rows = document.querySelectorAll('.table-row');
    let present = 0, absent = 0, excused = 0;
    
    rows.forEach(row => {
        const buttons = row.querySelectorAll('.status-btn');
        buttons.forEach(btn => {
            if (btn.classList.contains('active')) {
                const status = btn.getAttribute('data-status');
                if (status === 'present') present++;
                else if (status === 'absent') absent++;
                else if (status === 'excused') excused++;
            }
        });
    });
    
    // Cập nhật thống kê
    document.querySelector('[data-count="present"]').textContent = present;
    document.querySelector('[data-count="absent"]').textContent = absent;
    document.querySelector('[data-count="excused"]').textContent = excused;
    document.querySelector('[data-count="total"]').textContent = rows.length;
}

function saveAttendance() {
    // Thu thập dữ liệu điểm danh
    const rows = document.querySelectorAll('.table-row');
    const attendanceData = [];
    
    rows.forEach((row, index) => {
        const name = row.children[1].textContent;
        const studentCode = row.children[2].textContent;
        const activeButton = row.querySelector('.status-btn.active');
        const status = activeButton ? activeButton.getAttribute('data-status') : 'absent';
        
        attendanceData.push({
            id: index + 1,
            name: name,
            studentCode: studentCode,
            status: status
        });
    });
    
    // Hiển thị dữ liệu đã thu thập (sau này sẽ gửi lên server)
    console.log('Dữ liệu điểm danh:', attendanceData);
    alert(`Đã lưu điểm danh cho ${attendanceData.length} sinh viên!\nCó mặt: ${document.querySelector('[data-count="present"]').textContent}\nVắng: ${document.querySelector('[data-count="absent"]').textContent}\nVắng có phép: ${document.querySelector('[data-count="excused"]').textContent}`);
    
    // Sau này có API thì thay bằng:
    // fetch('/api/save-attendance', {
    //     method: 'POST',
    //     body: JSON.stringify(attendanceData)
    // })
}

// Khởi chạy khi trang tải xong
document.addEventListener('DOMContentLoaded', function() {
    console.log('Đang tải danh sách sinh viên...');
    console.log('Số lượng sinh viên:', window.mockStudents ? window.mockStudents.length : 0);
    
    renderStudentList();
    setupEventListeners();
    
    // Log để debug
    console.log('Danh sách sinh viên đã được render');
});




     document.addEventListener('DOMContentLoaded', function() {
    const selectWrappers = document.querySelectorAll('.select-wrapper');

    selectWrappers.forEach(wrapper => {
        const originalSelect = wrapper.querySelector('select');
        const options = originalSelect.querySelectorAll('option');
        const labelText = wrapper.previousElementSibling.textContent; // Lấy chữ "Lớp học phần" hoặc "Buổi"

        // 1. Tạo giao diện Trigger (nút bấm hiện tại)
        const trigger = document.createElement('div');
        trigger.className = 'select-trigger';
        trigger.innerHTML = `<span class="current-text">${options[0].text}</span><div class="select-arrow">▼</div>`;
        wrapper.appendChild(trigger);

        // 2. Tạo giao diện Menu Danh sách theo mẫu của bạn
        const menuHTML = `
            <div class="session-menu">
                <h3 class="menu-title">${labelText}</h3>
                <div class="search-box-container">
                    <input type="text" class="search-field" placeholder="Nhập nội dung cần tìm....">
                    <button class="search-submit">
                        <img src="img/search-icon.svg" alt="search">
                    </button>
                </div>
                <ul class="menu-list">
                    ${Array.from(options).map((opt, index) => `
                        <li class="menu-item ${index === 0 ? 'active' : ''}" data-value="${opt.value}">
                            ${opt.text}
                        </li>
                    `).join('')}
                </ul>
            </div>
        `;
        wrapper.insertAdjacentHTML('beforeend', menuHTML);

        const menu = wrapper.querySelector('.session-menu');
        const searchField = menu.querySelector('.search-field');
        const menuItems = menu.querySelectorAll('.menu-item');

        // 3. Sự kiện Click mở menu
        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            wrapper.classList.toggle('active-menu');
        });

        // 4. Sự kiện chọn item trong danh sách
        menuItems.forEach(item => {
            item.addEventListener('click', () => {
                // Cập nhật giao diện
                menu.querySelector('.menu-item.active').classList.remove('active');
                item.classList.add('active');
                trigger.querySelector('.current-text').textContent = item.textContent;
                
                // Cập nhật giá trị vào select thật để các logic JS khác không bị hỏng
                originalSelect.value = item.getAttribute('data-value');
                originalSelect.dispatchEvent(new Event('change')); // Kích hoạt sự kiện change nếu có
                
                wrapper.classList.remove('active-menu');
            });
        });

        // 5. Tính năng tìm kiếm trong menu
        searchField.addEventListener('input', (e) => {
            const filter = e.target.value.toLowerCase();
            menuItems.forEach(item => {
                const text = item.textContent.toLowerCase();
                item.style.display = text.includes(filter) ? 'block' : 'none';
            });
        });
    });

    // Click ra ngoài để đóng menu
    document.addEventListener('click', () => {
        selectWrappers.forEach(w => w.classList.remove('active-menu'));
    });
});
