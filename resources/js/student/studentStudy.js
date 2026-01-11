// ================= TAB SWITCHING =================
document.querySelectorAll('.tab-btn').forEach(tab => {
    tab.addEventListener('click', () => {
        // Remove active from all tabs
        document.querySelectorAll('.tab-btn').forEach(t => t.classList.remove('active'));
        // Add active to clicked tab
        tab.classList.add('active');

        // Hide all tab content
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        // Show selected tab content
        document.getElementById('tab-' + tab.dataset.tab).classList.add('active');
    });
});

// ================= MOCK DATA - CHI TIẾT HỌC KÌ =================
const semesterDetailData = {
    '2025-2026': [
        { code: 'CS101', name: 'Lập trình mạng', credit: 3, attendance: 10, midterm: 10, regular: 10, final: 10, total: 10, status: 'pass' },
        { code: 'CS101', name: 'Lập trình mạng', credit: 3, attendance: 10, midterm: 10, regular: 10, final: 10, total: 10, status: 'pass' },
        { code: 'CS101', name: 'Lập trình mạng', credit: 3, attendance: 7, midterm: 10, regular: 10, final: 0, total: 0, status: 'fail' },
        { code: 'CS101', name: 'Lập trình mạng', credit: 3, attendance: 10, midterm: 10, regular: 10, final: 1, total: 3.9, status: 'fail' }
    ],
    '2024-2025-2': [
        { code: 'CS102', name: 'Cơ sở dữ liệu', credit: 3, attendance: 9, midterm: 8, regular: 9, final: 8, total: 8.2, status: 'pass' },
        { code: 'CS103', name: 'Mạng máy tính', credit: 3, attendance: 10, midterm: 9, regular: 10, final: 9, total: 9.1, status: 'pass' }
    ],
    '2024-2025-1': [
        { code: 'CS104', name: 'Lập trình Web', credit: 3, attendance: 8, midterm: 7, regular: 8, final: 7, total: 7.3, status: 'pass' }
    ]
};

// ================= MOCK DATA - TOÀN KHÓA =================
const semesterSummaryData = [
    {
        name: 'Học kỳ 2 - Năm 2021-2022',
        gpa: 2.85,
        totalCredits: 13,
        courses: [
            { code: 'CS101', name: 'Lập trình mạng', credit: 3, totalScore: 4, grade: 'A', rating: 'Giỏi' },
            { code: 'CS101', name: 'Lập trình mạng', credit: 3, totalScore: 3, grade: 'B', rating: 'Khá' },
            { code: 'CS101', name: 'Lập trình mạng', credit: 3, totalScore: 2.1, grade: 'C', rating: 'Trung bình' },
            { code: 'CS101', name: 'Lập trình mạng', credit: 3, totalScore: 3.9, grade: 'A', rating: 'Giỏi' }
        ]
    },
    {
        name: 'Học kỳ 1 - Năm 2021-2022',
        gpa: 2.85,
        totalCredits: 13,
        courses: [
            { code: 'CS101', name: 'Lập trình mạng', credit: 3, totalScore: 4, grade: 'A', rating: 'Giỏi' },
            { code: 'CS101', name: 'Lập trình mạng', credit: 3, totalScore: 3, grade: 'B', rating: 'Khá' },
            { code: 'CS101', name: 'Lập trình mạng', credit: 3, totalScore: 2.1, grade: 'C', rating: 'Trung bình' },
            { code: 'CS101', name: 'Lập trình mạng', credit: 3, totalScore: 3.9, grade: 'A', rating: 'Giỏi' }
        ]
    }
];

// ================= RENDER CHI TIẾT HỌC KÌ =================
function renderDetailTable(semesterKey) {
    const tbody = document.getElementById('detail-tbody');
    tbody.innerHTML = ''; // Clear existing data

    const courses = semesterDetailData[semesterKey] || [];
    
    courses.forEach(course => {
        const row = tbody.insertRow();
        
        // Mã môn học
        row.insertCell().innerHTML = `<span class="course-code">${course.code}</span>`;
        
        // Tên môn học
        row.insertCell().textContent = course.name;
        
        // Số tín chỉ
        row.insertCell().textContent = course.credit;
        
        // Chuyên cần
        const attendanceCell = row.insertCell();
        attendanceCell.textContent = course.attendance;
        attendanceCell.className = 'score-cell';
        if (course.attendance < 8) attendanceCell.classList.add('score-red');
        
        // Giữa kì
        row.insertCell().textContent = course.midterm;
        
        // Thường xuyên
        row.insertCell().textContent = course.regular;
        
        // Cuối kì
        const finalCell = row.insertCell();
        finalCell.textContent = course.final;
        finalCell.className = 'score-cell';
        if (course.final < 5) finalCell.classList.add('score-red');
        
        // Tổng kết
        const totalCell = row.insertCell();
        totalCell.textContent = course.total;
        totalCell.className = 'score-cell';
        if (course.total < 5) totalCell.classList.add('score-red');
        else if (course.total < 7) totalCell.classList.add('score-orange');
        
        // Trạng thái
        const statusCell = row.insertCell();
        const statusBadge = document.createElement('span');
        statusBadge.className = `status-badge ${course.status === 'pass' ? 'status-pass' : 'status-fail'}`;
        statusBadge.textContent = course.status === 'pass' ? 'Đạt' : 'Không đạt';
        statusCell.appendChild(statusBadge);
    });
}

// ================= SEMESTER DROPDOWN CHANGE =================
document.getElementById('semester-dropdown').addEventListener('change', (e) => {
    renderDetailTable(e.target.value);
});

// ================= RENDER TOÀN KHÓA =================
function renderSemesterCards() {
    const container = document.getElementById('semester-list');
    container.innerHTML = '';

    semesterSummaryData.forEach(semester => {
        const section = document.createElement('div');
        section.className = 'semester-section';
        
        section.innerHTML = `
            <div class="semester-header">
                <h3 class="semester-name">${semester.name}</h3>
                <p class="semester-info">GPA: ${semester.gpa.toFixed(2)} | Tín chỉ: ${semester.totalCredits}</p>
            </div>
            
            <table class="semester-table">
                <thead>
                    <tr>
                        <th>Mã môn học</th>
                        <th>Tên môn học</th>
                        <th>Số tín chỉ</th>
                        <th>Tổng kết (hệ 10)</th>
                        <th>Tổng kết (hệ 4)</th>
                        <th>Điểm chữ</th>
                        <th>Xếp loại</th>
                    </tr>
                </thead>
                <tbody>
                    ${semester.courses.map(course => `
                        <tr>
                            <td><span class="course-code">${course.code}</span></td>
                            <td>${course.name}</td>
                            <td>${course.credit}</td>
                            <td>${course.totalScore}</td>
                            <td>${course.totalScore}</td>
                            <td><span class="grade-badge grade-${course.grade.toLowerCase()}">${course.grade}</span></td>
                            <td class="${
                                course.rating === 'Giỏi' ? 'rating-good' : 
                                course.rating === 'Khá' ? 'rating-average' : 
                                'rating-poor'
                            }">${course.rating}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
        
        container.appendChild(section);
    });
}

// ================= INITIALIZE =================
document.addEventListener('DOMContentLoaded', () => {
    // Render initial detail table
    renderDetailTable('2025-2026');
    
    // Render semester cards
    renderSemesterCards();
});
