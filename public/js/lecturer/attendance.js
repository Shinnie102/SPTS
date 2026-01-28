// attendance.js - Quản lý điểm danh với dữ liệu từ backend

// Biến toàn cục
let currentMeetingId = window.currentMeetingId;
let currentClassId = window.currentClassId;
let isLocked = window.isLocked;
let students = window.attendanceData || [];
let isMeetingLoading = false;
let tempMeeting = null;
const baseStudents = (window.attendanceData || []).map(s => ({ ...s, attendance_status_id: null }));

function setDatePickerVisible(visible) {
    const datePicker = document.getElementById('meeting-date-picker');
    if (!datePicker) return;
    datePicker.style.display = visible ? 'inline-block' : 'none';
}

function getDatePickerValue() {
    const datePicker = document.getElementById('meeting-date-picker');
    return datePicker ? datePicker.value : '';
}

function formatDateDDMMYYYY(dateString) {
    // expects YYYY-MM-DD (but may receive 'YYYY-MM-DD HH:mm:ss' or ISO)
    if (!dateString || typeof dateString !== 'string') return '';
    const dateOnly = dateString.split('T')[0].split(' ')[0];
    const parts = dateOnly.split('-');
    if (parts.length !== 3) return dateString;
    return `${parts[2]}/${parts[1]}/${parts[0]}`;
}

function setSaveButtonEnabled(enabled) {
    const saveBtn = document.getElementById('save-attendance-btn');
    if (!saveBtn) return;
    saveBtn.disabled = !enabled;
    saveBtn.style.opacity = enabled ? '1' : '0.6';
    saveBtn.style.cursor = enabled ? 'pointer' : 'not-allowed';
}

/**
 * Render danh sách sinh viên từ dữ liệu backend
 */
function renderStudentList() {
    const tableBody = document.getElementById('attendance-table-body');
    
    if (!tableBody) {
        console.error('Không tìm thấy element attendance-table-body');
        return;
    }
    
    tableBody.innerHTML = '';
    
    // ĐẢM BẢO reset lại các biến thống kê khi render mới
    resetStats();
    
    students.forEach((student, index) => {
        // XÁC ĐỊNH statusId (nếu null hoặc undefined thì mặc định là null)
        const statusId = student.attendance_status_id;
        
        const rowHTML = `
            <div class="table-row" data-enrollment-id="${student.enrollment_id}">
                <div>${index + 1}</div>
                <div>${student.name}</div>
                <div>${student.student_code}</div>
                <div class="status-buttons">
                    <button class="status-btn ${statusId == 1 ? 'active' : ''}" 
                            data-status="present" 
                            data-status-id="1"
                            ${isLocked ? 'disabled' : ''}>
                        Có mặt
                    </button>
                    <button class="status-btn ${statusId == 2 ? 'active' : ''}" 
                            data-status="absent" 
                            data-status-id="2"
                            ${isLocked ? 'disabled' : ''}>
                        Vắng
                    </button>
                    <button class="status-btn ${statusId == 3 ? 'active' : ''}" 
                            data-status="late" 
                            data-status-id="3"
                            ${isLocked ? 'disabled' : ''}>
                        Đi muộn
                    </button>
                    <button class="status-btn ${statusId == 4 ? 'active' : ''}" 
                            data-status="excused" 
                            data-status-id="4"
                            ${isLocked ? 'disabled' : ''}>
                        Có phép
                    </button>
                </div>
            </div>
        `;
        
        tableBody.innerHTML += rowHTML;
    });
    
    updateStats();
}

// Thêm hàm reset stats
function resetStats() {
    const present = document.querySelector('[data-count="present"]');
    const absent = document.querySelector('[data-count="absent"]');
    const late = document.querySelector('[data-count="late"]');
    const excused = document.querySelector('[data-count="excused"]');
    const total = document.querySelector('[data-count="total"]');

    if (present) present.textContent = 0;
    if (absent) absent.textContent = 0;
    if (late) late.textContent = 0;
    if (excused) excused.textContent = 0;
    if (total) total.textContent = students.length;
}
/**
 * Cập nhật thống kê
 */
function updateStats() {
    const stats = {
        present: students.filter(s => s.attendance_status_id == 1).length,
        absent: students.filter(s => s.attendance_status_id == 2).length,
        late: students.filter(s => s.attendance_status_id == 3).length,
        excused: students.filter(s => s.attendance_status_id == 4).length,
        total: students.length
    };
    
    const present = document.querySelector('[data-count="present"]');
    const absent = document.querySelector('[data-count="absent"]');
    const late = document.querySelector('[data-count="late"]');
    const excused = document.querySelector('[data-count="excused"]');
    const total = document.querySelector('[data-count="total"]');

    if (present) present.textContent = stats.present;
    if (absent) absent.textContent = stats.absent;
    if (late) late.textContent = stats.late;
    if (excused) excused.textContent = stats.excused;
    if (total) total.textContent = stats.total;
}

/**
 * Thiết lập event listeners
 */
function setupEventListeners() {
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('status-btn') && !e.target.disabled) {
            const row = e.target.closest('.table-row');
            const statusButtons = row.querySelectorAll('.status-btn');
            
            statusButtons.forEach(btn => {
                btn.classList.remove('active');
            });
            
            e.target.classList.add('active');
            
            // Cập nhật dữ liệu trong mảng students
            const enrollmentId = parseInt(row.getAttribute('data-enrollment-id'));
            const statusId = parseInt(e.target.getAttribute('data-status-id'));
            
            const studentIndex = students.findIndex(s => s.enrollment_id == enrollmentId);
            if (studentIndex !== -1) {
                students[studentIndex].attendance_status_id = statusId;
            }
            
            updateStats();
        }
        
        if (e.target.id === 'save-attendance-btn' || e.target.closest('#save-attendance-btn')) {
            saveAttendance();
        }

        if (e.target.id === 'add-meeting-btn' || e.target.closest('#add-meeting-btn')) {
            createTempMeeting();
        }
    });
}

function createTempMeeting() {
    const select = document.getElementById('session-select');
    if (!select) return;

    let tempOption = select.querySelector('option[value="temp"]');
    if (!tempOption) {
        tempOption = document.createElement('option');
        tempOption.value = 'temp';
        tempOption.textContent = 'Buổi mới (chưa lưu)';
        tempOption.setAttribute('data-meeting-info', 'Buổi mới (chưa lưu)');
        select.appendChild(tempOption);
    }

    select.value = 'temp';
    select.dispatchEvent(new Event('change'));
}

function rebuildSessionSelect(meetings, selectedMeetingId) {
    const select = document.getElementById('session-select');
    if (!select) return;

    // rebuild options
    select.innerHTML = '';
    meetings.forEach((m, idx) => {
        const option = document.createElement('option');
        option.value = String(m.class_meeting_id);
        const label = `Buổi ${idx + 1} - Ngày ${formatDateDDMMYYYY(m.meeting_date)}`;
        option.textContent = label;
        option.setAttribute('data-meeting-info', label);
        if (String(m.class_meeting_id) === String(selectedMeetingId)) {
            option.selected = true;
        }
        select.appendChild(option);
    });

    if (meetings.length === 0) {
        const option = document.createElement('option');
        option.value = '';
        option.textContent = 'Chưa có buổi điểm danh';
        option.selected = true;
        select.appendChild(option);
    }

    // remove existing custom UI for this wrapper so it can be re-initialized
    const wrapper = select.closest('.select-wrapper');
    if (wrapper) {
        const trigger = wrapper.querySelector('.select-trigger');
        const menu = wrapper.querySelector('.session-menu');
        if (trigger) trigger.remove();
        if (menu) menu.remove();
        wrapper.classList.remove('active-menu');
        wrapper.classList.remove('dropdown-enhanced');
    }

    initializeCustomDropdowns();
}

/**
 * Lấy dữ liệu điểm danh cho buổi học mới
 */
async function loadAttendanceData(meetingId) {
    try {
        isMeetingLoading = true;

        // Reset UI ngay khi đổi buổi để tránh giữ state cũ
        students = [];
        renderStudentList();

        // Chặn thao tác trong lúc đang load
        updateLockStatus(true);

        const response = await fetch(`/lecturer/class/${currentClassId}/attendance-data/${meetingId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            students = data.students;
            isLocked = data.isLocked;
            currentMeetingId = meetingId;
            
            // Cập nhật UI
            renderStudentList();
            updateLockStatus(isLocked);
            
            // Cập nhật dropdown nếu có
            const sessionSelect = document.getElementById('session-select');
            const sessionWrapper = sessionSelect ? sessionSelect.closest('.select-wrapper') : null;
            const selectTrigger = sessionWrapper ? sessionWrapper.querySelector('.select-trigger .current-text') : null;
            if (selectTrigger) {
                const selectedOption = document.querySelector(`#session-select option[value="${meetingId}"]`);
                if (selectedOption) {
                    selectTrigger.textContent = selectedOption.getAttribute('data-meeting-info') || selectedOption.textContent;
                }
            }
        }
    } catch (error) {
        console.error('Lỗi khi tải dữ liệu điểm danh:', error);
        alert('Không thể tải dữ liệu điểm danh. Vui lòng thử lại.');
    } finally {
        isMeetingLoading = false;
    }
}

/**
 * Cập nhật trạng thái khóa của giao diện
 */
function updateLockStatus(locked) {
    isLocked = locked;

    // Không ẩn nút Lưu; chỉ disable
    const saveBtn = document.getElementById('save-attendance-btn');
    if (saveBtn) {
        saveBtn.disabled = locked;
        if (saveBtn.parentElement) {
            saveBtn.parentElement.style.display = 'block';
        }
    }
    
    // Enable/disable các nút trạng thái
    const statusButtons = document.querySelectorAll('.status-btn');
    statusButtons.forEach(btn => {
        btn.disabled = locked;
    });

    // Disable session controls when locked
    const sessionSelect = document.getElementById('session-select');
    if (sessionSelect) sessionSelect.disabled = locked;
    const addMeetingBtn = document.getElementById('add-meeting-btn');
    if (addMeetingBtn) addMeetingBtn.disabled = locked;
    const datePicker = document.getElementById('meeting-date-picker');
    if (datePicker) datePicker.disabled = locked;
    
    // Thêm/xóa class table-locked
    const tableContainer = document.querySelector('.attendance-table-container');
    if (!tableContainer) {
        return;
    }
    if (locked) {
        tableContainer.classList.add('table-locked');
    } else {
        tableContainer.classList.remove('table-locked');
    }
}

/**
 * Lưu điểm danh
 */
async function saveAttendance() {
    if (isLocked) {
        alert('Buổi học này đã được điểm danh và không thể thay đổi.');
        return;
    }

    if (isMeetingLoading) {
        alert('Đang tải dữ liệu buổi học. Vui lòng đợi...');
        return;
    }
    
    if (!currentMeetingId || currentMeetingId !== 'temp') {
        alert('Vui lòng bấm + để tạo buổi mới và chọn ngày trước khi lưu.');
        return;
    }

    if (!tempMeeting || !tempMeeting.meeting_date) {
        alert('Vui lòng chọn ngày cho buổi điểm danh.');
        return;
    }
    
    // Chuẩn bị dữ liệu
    const attendanceData = [];
    const rows = document.querySelectorAll('.table-row');

    if (!rows || rows.length === 0) {
        alert('Không có sinh viên để điểm danh cho buổi học này.');
        return;
    }
    
    let hasAnySelected = false;

    rows.forEach((row) => {
        const enrollmentId = parseInt(row.getAttribute('data-enrollment-id'));
        const activeButton = row.querySelector('.status-btn.active');
        
        if (activeButton) {
            const statusId = parseInt(activeButton.getAttribute('data-status-id'));
            hasAnySelected = true;
            attendanceData.push({
                enrollment_id: enrollmentId,
                status: statusId
            });
        } else {
            // Mặc định là vắng nếu không chọn
            attendanceData.push({
                enrollment_id: enrollmentId,
                status: 2 // ABSENT
            });
        }
    });

    if (!hasAnySelected) {
        alert('Vui lòng điểm danh ít nhất 1 sinh viên trước khi lưu.');
        return;
    }
    
    try {
        const response = await fetch(`/lecturer/class/${currentClassId}/attendance/save`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.csrfToken
            },
            body: JSON.stringify({
                class_section_id: currentClassId,
                meeting_date: tempMeeting.meeting_date,
                attendance: attendanceData
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
            // Rebuild dropdown từ server và chọn buổi vừa tạo
            if (result.meetings && result.created_meeting_id) {
                rebuildSessionSelect(result.meetings, String(result.created_meeting_id));
                const select = document.getElementById('session-select');
                if (select) {
                    select.value = String(result.created_meeting_id);
                    select.dispatchEvent(new Event('change'));
                }
            }

            tempMeeting = null;
            setDatePickerVisible(false);
        } else {
            alert('Lỗi: ' + result.message);
        }
    } catch (error) {
        console.error('Lỗi khi lưu điểm danh:', error);
        alert('Đã xảy ra lỗi khi lưu điểm danh. Vui lòng thử lại.');
    }
}

/**
 * Khởi tạo custom dropdown cho buổi học
 */
function initializeCustomDropdowns() {
    const selectWrappers = document.querySelectorAll('.select-wrapper');
    
    selectWrappers.forEach(wrapper => {
        const originalSelect = wrapper.querySelector('select');
        if (!originalSelect) return;

        // Class dropdown is handled by dropdown-header.js (shared across pages)
        if (originalSelect.id === 'class-select') return;

        // Đã được khởi tạo rồi thì bỏ qua
        if (wrapper.querySelector('.select-trigger') || wrapper.querySelector('.session-menu')) {
            return;
        }

        const options = originalSelect.querySelectorAll('option');
        const labelText = wrapper.previousElementSibling.textContent;

        // Tìm option đang được chọn (handle empty options)
        const selectedOption = Array.from(options).find(opt => opt.selected) || options[0] || null;
        const selectedIndex = Math.max(0, Array.from(options).findIndex(opt => opt.selected));
        
        // Tạo giao diện Trigger
        const trigger = document.createElement('div');
        trigger.className = 'select-trigger';
        trigger.innerHTML = `<span class="current-text">${selectedOption ? selectedOption.text : 'Chưa có lựa chọn'}</span><div class="select-arrow">▼</div>`;
        wrapper.appendChild(trigger);

        // Enable enhanced-mode styling (hide native select, show trigger/menu)
        wrapper.classList.add('dropdown-enhanced');
        
        // Tạo giao diện Menu
        const menuHTML = `
            <div class="session-menu">
                <h3 class="menu-title">${labelText}</h3>
                <div class="search-box-container">
                    <input type="text" class="search-field" placeholder="Nhập nội dung cần tìm....">
                    <button class="search-submit">
                       <img src="${window.ASSETS.searchIcon}" alt="search">
                    </button>
                </div>
                <ul class="menu-list">
                    ${Array.from(options).map((opt, index) => `
                        <li class="menu-item ${index === selectedIndex ? 'active' : ''}" 
                            data-value="${opt.value}"
                            data-meeting-info="${opt.getAttribute('data-meeting-info')}">
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
        
        // Sự kiện Click mở menu
        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            wrapper.classList.toggle('active-menu');
        });
        
        // Sự kiện chọn item
        menuItems.forEach(item => {
            item.addEventListener('click', () => {
                menu.querySelector('.menu-item.active').classList.remove('active');
                item.classList.add('active');
                trigger.querySelector('.current-text').textContent = item.textContent;
                
                const meetingId = item.getAttribute('data-value');
                originalSelect.value = meetingId;
                originalSelect.dispatchEvent(new Event('change'));
                
                wrapper.classList.remove('active-menu');
            });
        });
        
        // Tính năng tìm kiếm
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

/**
 * Thiết lập xử lý thay đổi lớp học
 */
function setupClassChangeHandler() {
    const classSelect = document.getElementById('class-select');
    
    if (classSelect) {
        classSelect.addEventListener('change', function() {
            const selectedClassId = this.value;
            if (!selectedClassId) return;
            
            // Xác định URL hiện tại
            const currentUrl = window.location.pathname;
            
            // Tạo URL mới dựa trên route pattern
            const urlParts = currentUrl.split('/');
            
            for (let i = urlParts.length - 1; i >= 0; i--) {
                if (urlParts[i] && !isNaN(urlParts[i]) && urlParts[i] !== '') {
                    urlParts[i] = selectedClassId;
                    break;
                }
            }
            
            // Chuyển hướng
            window.location.href = urlParts.join('/');
        });
    }
}

/**
 * Luôn reload dữ liệu khi đổi buổi điểm danh
 * (Không phụ thuộc custom dropdown)
 */
function setupMeetingChangeHandler() {
    const sessionSelect = document.getElementById('session-select');

    if (!sessionSelect) return;

    sessionSelect.addEventListener('change', function () {
        const meetingId = this.value;

        // Chưa có buổi nào
        if (!meetingId) {
            currentMeetingId = null;
            tempMeeting = null;
            setDatePickerVisible(false);
            students = baseStudents.map(s => ({ ...s, attendance_status_id: null }));
            renderStudentList();
            updateLockStatus(true);
            setSaveButtonEnabled(false);
            return;
        }

        // Buổi tạm (chưa lưu DB)
        if (meetingId === 'temp') {
            currentMeetingId = 'temp';
            tempMeeting = { id: 'temp', meeting_date: getDatePickerValue() || null };
            setDatePickerVisible(true);
            updateLockStatus(false);
            students = baseStudents.map(s => ({ ...s, attendance_status_id: null }));
            renderStudentList();
            setSaveButtonEnabled(!!tempMeeting.meeting_date);
            return;
        }

        // Buổi đã tồn tại
        currentMeetingId = meetingId;
        tempMeeting = null;
        setDatePickerVisible(false);
        setSaveButtonEnabled(false);
        loadAttendanceData(meetingId);
    });
}

/**
 * Khởi tạo
 */
document.addEventListener('DOMContentLoaded', function() {
    // Trang Grading/Report/Status cũng dùng attendance.js cho header dropdown,
    // nhưng không có bảng điểm danh. Guard để không throw lỗi.
    const hasAttendanceUI = !!document.getElementById('attendance-table-body');

    

    // Shared (header)
    initializeCustomDropdowns();
    setupClassChangeHandler();

    if (!hasAttendanceUI) {
        return;
    }

    // Attendance-only
    renderStudentList();
    setupEventListeners();
    setupMeetingChangeHandler();

    const datePicker = document.getElementById('meeting-date-picker');
    if (datePicker) {
        datePicker.addEventListener('change', function () {
            if (currentMeetingId === 'temp') {
                tempMeeting = tempMeeting || { id: 'temp', meeting_date: null };
                tempMeeting.meeting_date = this.value || null;
                setSaveButtonEnabled(!!tempMeeting.meeting_date);
            }
        });
    }

    // Initial state
    if (currentMeetingId) {
        setSaveButtonEnabled(false);
    } else {
        updateLockStatus(true);
        setSaveButtonEnabled(false);
    }
    
    
});