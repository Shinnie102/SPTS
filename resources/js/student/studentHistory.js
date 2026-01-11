// ================= MOCK DATA =================
const attendanceData = {
    totalProgress: 93, // Tổng quan toàn khóa
    semesters: {
        '2025-2026': [
            {
                code: 'CS101',
                name: 'Lập trình mạng',
                totalSessions: 15,
                present: 15,
                absent: 0,
                late: 0,
                percentage: 100,
                status: 'pass',
                details: [
                    { stt: 1, date: '07/06/2025', status: 'present' },
                    { stt: 2, date: '14/06/2025', status: 'present' },
                    { stt: 3, date: '21/06/2025', status: 'present' }
                ]
            },
            {
                code: 'CS101',
                name: 'Lập trình mạng',
                totalSessions: 30,
                present: 20,
                absent: 10,
                late: 2,
                percentage: 63.3,
                status: 'fail',
                details: [
                    { stt: 1, date: '07/06/2025', status: 'present' },
                    { stt: 2, date: '14/06/2025', status: 'absent' },
                    { stt: 3, date: '21/06/2025', status: 'absent' },
                    { stt: 4, date: '28/06/2025', status: 'present' },
                    { stt: 5, date: '05/07/2025', status: 'late' },
                    { stt: 6, date: '12/07/2025', status: 'absent' },
                    { stt: 7, date: '19/07/2025', status: 'absent' },
                    { stt: 8, date: '26/07/2025', status: 'present' },
                    { stt: 9, date: '02/08/2025', status: 'present' },
                    { stt: 10, date: '09/08/2025', status: 'absent' }
                ]
            },
            {
                code: 'CS101',
                name: 'Lập trình mạng',
                totalSessions: 15,
                present: 6,
                absent: 4,
                late: 0,
                percentage: null,
                status: 'fail',
                details: [
                    { stt: 1, date: '07/06/2025', status: 'present' },
                    { stt: 2, date: '14/06/2025', status: 'absent' }
                ]
            },
            {
                code: 'CS101',
                name: 'Lập trình mạng',
                totalSessions: 15,
                present: 5,
                absent: 0,
                late: 0,
                percentage: null,
                status: 'learning',
                details: []
            }
        ],
        '2024-2025-2': [
            {
                code: 'CS102',
                name: 'Cơ sở dữ liệu',
                totalSessions: 20,
                present: 18,
                absent: 2,
                late: 1,
                percentage: 90,
                status: 'pass',
                details: [
                    { stt: 1, date: '15/01/2025', status: 'present' },
                    { stt: 2, date: '22/01/2025', status: 'absent' }
                ]
            }
        ]
    }
};

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

// ================= SEMESTER DROPDOWN CHANGE =================
document.getElementById('semester-dropdown').addEventListener('change', (e) => {
    renderAttendanceTable(e.target.value);
});

// ================= INITIALIZE =================
document.addEventListener('DOMContentLoaded', () => {
    // Render progress bar
    renderProgressBar();
    
    // Render initial attendance table
    renderAttendanceTable('2025-2026');
});
