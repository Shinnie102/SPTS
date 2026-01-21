// ================= DATA FROM SERVER =================
// attendanceData is passed from Laravel blade template via @json directive
// Data is injected via <script> tag in studentHistory.blade.php

// ================= RENDER PROGRESS BAR =================
function renderProgressBar(semesterKey = null) {
    const progressFill = document.querySelector('.progress-bar-fill');
    const progressText = document.querySelector('.progress-percentage');
    
    if (!progressFill || !progressText) {
        console.error('Progress bar elements not found');
        return;
    }

    let progressValue = 0;

    // Nếu có semesterKey, hiển thị progress của semester đó
    if (semesterKey && attendanceData.semesters && attendanceData.semesters[semesterKey]) {
        progressValue = attendanceData.semesters[semesterKey].progress || 0;
    } 
    // Nếu không, hiển thị totalProgress (tổng thể)
    else if (attendanceData.totalProgress !== undefined) {
        progressValue = attendanceData.totalProgress;
    }
    else {
        progressValue = 0;
    }

    // Cập nhật UI
    progressFill.style.width = progressValue + '%';
    progressText.textContent = progressValue + '%';
    
    // Thêm class cho màu sắc theo tỉ lệ
    progressFill.className = 'progress-bar-fill';
    if (progressValue >= 80) {
        progressFill.classList.add('progress-high');
    } else if (progressValue >= 60) {
        progressFill.classList.add('progress-medium');
    } else {
        progressFill.classList.add('progress-low');
    }
}

// ================= RENDER ATTENDANCE TABLE =================
function renderAttendanceTable(semesterKey) {
    const tbody = document.getElementById('attendance-tbody');
    
    if (!tbody) {
        console.error('Table tbody not found');
        return;
    }
    
    tbody.innerHTML = '';

    // Xử lý data structure từ backend: semesters[key] = { progress, courses: [...] }
    let courses = [];
    
    if (attendanceData.semesters && attendanceData.semesters[semesterKey]) {
        const semesterData = attendanceData.semesters[semesterKey];
        
        // Nếu có property 'courses', dùng nó
        if (semesterData.courses && Array.isArray(semesterData.courses)) {
            courses = semesterData.courses;
        }
        // Fallback: nếu semesterData là array trực tiếp
        else if (Array.isArray(semesterData)) {
            courses = semesterData;
        }
    }

    if (courses.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px; color: #94a3b8;">Không có dữ liệu chuyên cần cho học kỳ này</td></tr>';
        return;
    }
    
    courses.forEach((course, index) => {
        // Main course row
        const row = tbody.insertRow();
        row.className = 'course-row';
        row.dataset.courseIndex = index;
        
        // Mã lớp học (with expand icon)
        const classCodeCell = row.insertCell();
        classCodeCell.innerHTML = `
            <span class="expand-icon">▶</span>
            <span class="course-code">${course.class_code || 'N/A'}</span>
        `;
        
        // Mã môn học
        const codeCell = row.insertCell();
        codeCell.innerHTML = `<span class="course-code">${course.code}</span>`;
        
        // Tên môn học
        row.insertCell().textContent = course.name;
        
        // Tổng số buổi
        row.insertCell().textContent = course.totalSessions;
        
        // Có mặt
        row.insertCell().textContent = course.present;
        
        // Vắng mặt
        const absentCell = row.insertCell();
        absentCell.textContent = course.absent;
        if (course.absent > 0) {
            absentCell.style.color = '#ef4444';
            absentCell.style.fontWeight = '600';
        }
        
        // Đi muộn
        row.insertCell().textContent = course.late;
        
        // Tỷ lệ
        const percentCell = row.insertCell();
        if (course.percentage !== null) {
            percentCell.textContent = course.percentage.toFixed(1) + '%';
            if (course.percentage >= 80) {
                percentCell.className = 'percentage-high';
            } else if (course.percentage >= 60) {
                percentCell.className = 'percentage-medium';
            } else {
                percentCell.className = 'percentage-low';
            }
        } else {
            percentCell.textContent = '-';
        }
        
        // Trạng thái
        const statusCell = row.insertCell();
        const statusBadge = document.createElement('span');
        statusBadge.className = 'status-badge';
        
        switch (course.status) {
            case 'pass':
                statusBadge.classList.add('status-pass');
                statusBadge.textContent = 'Đạt';
                break;
            case 'fail':
                statusBadge.classList.add('status-fail');
                statusBadge.textContent = 'Không đạt';
                break;
            case 'warning':
                statusBadge.classList.add('status-warning');
                statusBadge.textContent = 'Cảnh báo';
                break;
        }
        statusCell.appendChild(statusBadge);
        
        // Detail row (hidden by default)
        const detailRow = tbody.insertRow();
        detailRow.className = 'detail-row';
        const detailCell = detailRow.insertCell();
        detailCell.colSpan = 9;
        detailCell.className = 'detail-cell';
        
        detailCell.innerHTML = `
            <div class="detail-content">
                <p class="detail-title">Trạng thái</p>
                <table class="detail-table">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Ngày học</th>
                            <th>Giờ học</th>
                            <th>Phòng</th>
                            <th>Chủ đề</th>
                            <th>Giờ check-in</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${course.details.length > 0 ? course.details.map(detail => {
                            let statusClass = '';
                            let statusText = '';
                            
                            switch (detail.status) {
                                case 'present':
                                    statusClass = 'detail-present';
                                    statusText = 'Có mặt';
                                    break;
                                case 'absent':
                                    statusClass = 'detail-absent';
                                    statusText = 'Vắng mặt';
                                    break;
                                case 'late':
                                    statusClass = 'detail-late';
                                    statusText = 'Đi muộn';
                                    break;
                                case 'excused':
                                    statusClass = 'detail-excused';
                                    statusText = 'Có phép';
                                    break;
                                default:
                                    statusClass = 'detail-absent';
                                    statusText = detail.status;
                                    break;
                            }
                            
                            return `
                                <tr>
                                    <td>${detail.stt}</td>
                                    <td>${detail.date}</td>
                                    <td>${detail.time || '-'}</td>
                                    <td>${detail.room || '-'}</td>
                                    <td style="max-width: 200px; text-align: left;">${detail.topic || '-'}</td>
                                    <td>${detail.check_in_time || '-'}</td>
                                    <td><span class="detail-status ${statusClass}">${statusText}</span></td>
                                </tr>
                            `;
                        }).join('') : '<tr><td colspan="7" style="text-align: center; color: #94a3b8;">Chưa có dữ liệu</td></tr>'}
                    </tbody>
                </table>
            </div>
        `;
        
        // Add click event to toggle details
        row.addEventListener('click', (e) => {
            const icon = row.querySelector('.expand-icon');
            icon.classList.toggle('expanded');
            detailRow.classList.toggle('show');
        });
    });
}

// ================= POPULATE SEMESTER DROPDOWN =================
function populateSemesterDropdown() {
    const dropdown = document.getElementById('semester-dropdown');
    dropdown.innerHTML = ''; // Clear existing options
    
    if (!attendanceData || !attendanceData.semesters) {
        console.error('No semester data available for dropdown');
        dropdown.innerHTML = '<option value="">Không có dữ liệu</option>';
        return null;
    }
    
    // Convert semesters object to array and sort by sort_key (newest first)
    const semestersArray = Object.entries(attendanceData.semesters).map(([key, data]) => ({
        key: key,
        sort_key: data.sort_key || 0,
        semester_name: data.semester_name || key
    })).sort((a, b) => (b.sort_key || 0) - (a.sort_key || 0));
    
    if (semestersArray.length === 0) {
        dropdown.innerHTML = '<option value="">Không có dữ liệu</option>';
        return null;
    }
    
    console.log('Populating dropdown with semesters:', semestersArray);
    
    // Add options for each semester
    semestersArray.forEach((semester, index) => {
        const option = document.createElement('option');
        option.value = semester.key;
        option.textContent = semester.semester_name;
        if (index === 0) option.selected = true; // Select first semester by default
        dropdown.appendChild(option);
    });
    
    return semestersArray[0].key; // Return first semester key
}

// ================= SEMESTER DROPDOWN CHANGE =================
document.getElementById('semester-dropdown').addEventListener('change', (e) => {
    const selectedSemester = e.target.value;
    // Cập nhật bảng dữ liệu
    renderAttendanceTable(selectedSemester);
    // Cập nhật progress bar theo học kỳ đã chọn
    renderProgressBar(selectedSemester);
});

// ================= INITIALIZE =================
document.addEventListener('DOMContentLoaded', () => {
    console.log('Initializing with data:', attendanceData);
    
    // Kiểm tra data hợp lệ
    if (!attendanceData || !attendanceData.semesters) {
        console.error('Invalid attendance data structure');
        return;
    }
    
    // Populate semester dropdown và render table cho first semester
    const firstSemester = populateSemesterDropdown();
    
    if (firstSemester) {
        // Render progress bar cho first semester
        renderProgressBar(firstSemester);
        // Render table
        renderAttendanceTable(firstSemester);
    } else {
        // Không có dữ liệu
        const tbody = document.getElementById('attendance-tbody');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px;">⚠️ Chưa có dữ liệu chuyên cần</td></tr>';
        }
        // Set progress bar = 0
        renderProgressBar();
    }
});
