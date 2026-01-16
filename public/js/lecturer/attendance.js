// attendance.js - Hiển thị danh sách sinh viên và quản lý dropdown

function renderStudentList() {
    const students = window.mockStudents || [];
    const tableBody = document.getElementById('attendance-table-body');
    
    if (!tableBody) {
        console.error('Không tìm thấy element attendance-table-body');
        return;
    }
    
    tableBody.innerHTML = '';
    
    students.forEach((student, index) => {
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
    
    updateStats(students);
}

function updateStats(students) {
    const stats = {
        present: students.filter(s => s.status === 'present').length,
        absent: students.filter(s => s.status === 'absent').length,
        excused: students.filter(s => s.status === 'excused').length,
        total: students.length
    };
    
    document.querySelector('[data-count="present"]').textContent = stats.present;
    document.querySelector('[data-count="absent"]').textContent = stats.absent;
    document.querySelector('[data-count="excused"]').textContent = stats.excused;
    document.querySelector('[data-count="total"]').textContent = stats.total;
}

function setupEventListeners() {
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('status-btn')) {
            const row = e.target.closest('.table-row');
            const statusButtons = row.querySelectorAll('.status-btn');
            
            statusButtons.forEach(btn => {
                btn.classList.remove('active');
            });
            
            e.target.classList.add('active');
            updateAllStats();
        }
        
        if (e.target.id === 'save-attendance-btn' || e.target.closest('#save-attendance-btn')) {
            saveAttendance();
        }
    });
}

function updateAllStats() {
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
    
    document.querySelector('[data-count="present"]').textContent = present;
    document.querySelector('[data-count="absent"]').textContent = absent;
    document.querySelector('[data-count="excused"]').textContent = excused;
    document.querySelector('[data-count="total"]').textContent = rows.length;
}

function saveAttendance() {
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
    
    console.log('Dữ liệu điểm danh:', attendanceData);
    alert(`Đã lưu điểm danh cho ${attendanceData.length} sinh viên!\nCó mặt: ${document.querySelector('[data-count="present"]').textContent}\nVắng: ${document.querySelector('[data-count="absent"]').textContent}\nVắng có phép: ${document.querySelector('[data-count="excused"]').textContent}`);
}

// CUSTOM DROPDOWN FUNCTIONS

function initializeCustomDropdowns() {
    const selectWrappers = document.querySelectorAll('.select-wrapper');

    selectWrappers.forEach(wrapper => {
        const originalSelect = wrapper.querySelector('select');
        const options = originalSelect.querySelectorAll('option');
        const labelText = wrapper.previousElementSibling.textContent;

        // TÌM OPTION ĐANG ĐƯỢC CHỌN (SELECTED)
        const selectedOption = Array.from(options).find(opt => opt.selected) || options[0];
        const selectedIndex = Array.from(options).findIndex(opt => opt.selected);

        // 1. Tạo giao diện Trigger với option đang được chọn
        const trigger = document.createElement('div');
        trigger.className = 'select-trigger';
        trigger.innerHTML = `<span class="current-text">${selectedOption.text}</span><div class="select-arrow">▼</div>`;
        wrapper.appendChild(trigger);

        // 2. Tạo giao diện Menu Danh sách
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
                        <li class="menu-item ${index === selectedIndex ? 'active' : ''}" data-value="${opt.value}">
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
                menu.querySelector('.menu-item.active').classList.remove('active');
                item.classList.add('active');
                trigger.querySelector('.current-text').textContent = item.textContent;
                
                originalSelect.value = item.getAttribute('data-value');
                originalSelect.dispatchEvent(new Event('change'));
                
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
}

// CLASS CHANGE HANDLER

function setupClassChangeHandler() {
    const classSelect = document.getElementById('class-select');
    
    if (classSelect) {
        classSelect.addEventListener('change', function() {
            const selectedClassId = this.value;
            if (!selectedClassId) return;
            
            // Xác định URL hiện tại
            const currentUrl = window.location.pathname;
            
            // Tạo URL mới dựa trên route pattern hiện tại
            // Thay thế ID lớp trong URL hiện tại
            const urlParts = currentUrl.split('/');
            
            // Tìm vị trí của ID trong URL (thường là phần tử cuối hoặc gần cuối)
            for (let i = urlParts.length - 1; i >= 0; i--) {
                if (urlParts[i] && !isNaN(urlParts[i]) && urlParts[i] !== '') {
                    // Đây có thể là ID lớp
                    urlParts[i] = selectedClassId;
                    break;
                }
            }
            
            // Ghép lại URL
            const newUrl = urlParts.join('/');
            
            // Chuyển hướng
            window.location.href = newUrl;
        });
    }
}

// INITIALIZATION

document.addEventListener('DOMContentLoaded', function() {
    console.log('Đang tải danh sách sinh viên...');
    console.log('Số lượng sinh viên:', window.mockStudents ? window.mockStudents.length : 0);
    
    renderStudentList();
    setupEventListeners();
    initializeCustomDropdowns();
    setupClassChangeHandler();
    
    console.log('Danh sách sinh viên đã được render');
    
    // Debug: Kiểm tra select
    const classSelect = document.getElementById('class-select');
    if (classSelect) {
        console.log('Class select value:', classSelect.value);
        console.log('Selected option index:', classSelect.selectedIndex);
        console.log('Selected option text:', classSelect.options[classSelect.selectedIndex]?.text);
    }
});