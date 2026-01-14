// ================= DATA FROM SERVER =================
// attendanceData is passed from Laravel blade template via @json directive
// Data is injected via <script> tag in studentHistory.blade.php

// ================= RENDER PROGRESS BAR =================
function renderProgressBar() {
    const progressFill = document.querySelector('.progress-bar-fill');
    const progressText = document.querySelector('.progress-percentage');
    
    if (progressFill && progressText) {
        progressFill.style.width = attendanceData.totalProgress + '%';
        progressText.textContent = attendanceData.totalProgress + '%';
    }
}

// ================= RENDER ATTENDANCE TABLE =================
function renderAttendanceTable(semesterKey) {
    const tbody = document.getElementById('attendance-tbody');
    tbody.innerHTML = '';

    const courses = attendanceData.semesters[semesterKey] || [];
    
    courses.forEach((course, index) => {
        // Main course row
        const row = tbody.insertRow();
        row.className = 'course-row';
        row.dataset.courseIndex = index;
        
        // Mã môn học (with expand icon)
        const codeCell = row.insertCell();
        codeCell.innerHTML = `
            <span class="expand-icon">▶</span>
            <span class="course-code">${course.code}</span>
        `;
        
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
            case 'learning':
                statusBadge.classList.add('status-learning');
                statusBadge.textContent = 'Đang học';
                break;
        }
        statusCell.appendChild(statusBadge);
        
        // Detail row (hidden by default)
        const detailRow = tbody.insertRow();
        detailRow.className = 'detail-row';
        const detailCell = detailRow.insertCell();
        detailCell.colSpan = 8;
        detailCell.className = 'detail-cell';
        
        detailCell.innerHTML = `
            <div class="detail-content">
                <p class="detail-title">Trạng thái</p>
                <table class="detail-table">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Ngày học</th>
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
                                    statusText = 'Đi muộn';
                                    break;
                            }
                            
                            return `
                                <tr>
                                    <td>${detail.stt}</td>
                                    <td>${detail.date}</td>
                                    <td><span class="detail-status ${statusClass}">${statusText}</span></td>
                                </tr>
                            `;
                        }).join('') : '<tr><td colspan="3" style="text-align: center; color: #94a3b8;">Chưa có dữ liệu</td></tr>'}
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
    
    // Get all semester keys from data
    const semesters = Object.keys(attendanceData.semesters);
    
    if (semesters.length === 0) {
        dropdown.innerHTML = '<option value="">Không có dữ liệu</option>';
        return null;
    }
    
    // Add options for each semester
    semesters.forEach((semester, index) => {
        const option = document.createElement('option');
        option.value = semester;
        option.textContent = semester;
        if (index === 0) option.selected = true; // Select first semester by default
        dropdown.appendChild(option);
    });
    
    return semesters[0]; // Return first semester key
}

// ================= SEMESTER DROPDOWN CHANGE =================
document.getElementById('semester-dropdown').addEventListener('change', (e) => {
    renderAttendanceTable(e.target.value);
});

// ================= INITIALIZE =================
document.addEventListener('DOMContentLoaded', () => {
    console.log('Initializing with data:', attendanceData);
    
    // Render progress bar
    renderProgressBar();
    
    // Populate semester dropdown and render table for first semester
    const firstSemester = populateSemesterDropdown();
    if (firstSemester) {
        renderAttendanceTable(firstSemester);
    } else {
        // No data available
        document.querySelector('.table-body').innerHTML = '<p style="text-align: center; padding: 20px;">Không có dữ liệu chuyên cần</p>';
    }
});
