// ================= CHECK DATA FROM BACKEND =================
console.log('Score Data from Backend:', scoreData);

// ================= POPULATE SEMESTER DROPDOWN =================
function populateSemesterDropdown() {
    const dropdown = document.getElementById('semester-dropdown');
    dropdown.innerHTML = ''; // Clear existing options
    
    if (!scoreData || !scoreData.semesters) {
        console.error('No semester data available for dropdown');
        dropdown.innerHTML = '<option value="">Không có dữ liệu</option>';
        return;
    }
    
    // Convert to array and sort by sort_key (numeric) descending (newest first)
    const semestersArray = Object.entries(scoreData.semesters).map(([key, data]) => ({
        key: key,
        sort_key: data.sort_key || 0,
        semester_name: data.semester_name || key
    })).sort((a, b) => b.sort_key - a.sort_key); // Descending order
    
    console.log('Populating dropdown with semesters:', semestersArray);
    
    semestersArray.forEach(semester => {
        const option = document.createElement('option');
        option.value = semester.key;
        option.textContent = semester.semester_name;
        dropdown.appendChild(option);
    });
    
    return semestersArray[0]?.key; // Return first semester key
}

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

// ================= RENDER CHI TIẾT HỌC KÌ =================
function renderDetailTable(semesterKey) {
    const tbody = document.getElementById('detail-tbody');
    const thead = document.querySelector('.detail-table thead tr');
    tbody.innerHTML = ''; // Clear existing data

    console.log('Rendering detail table for semester:', semesterKey);
    
    // Get semester data from backend
    const semesterData = scoreData?.semesters?.[semesterKey];
    
    if (!semesterData || !Array.isArray(semesterData.courses)) {
        console.error('No courses found for semester:', semesterKey);
        tbody.innerHTML = '<tr><td colspan="20" style="text-align:center;">Không có dữ liệu</td></tr>';
        return;
    }
    
    // Lấy danh sách components ĐỘNG từ semester data
    const components = semesterData.components || [];
    console.log('Components for semester:', components);
    
    // Render THEAD động
    thead.innerHTML = `
        <th>Mã lớp học</th>
        <th>Mã môn học</th>
        <th>Tên môn học</th>
        <th>Số tín chỉ</th>
        ${components.map(comp => `<th>${comp.name} (${comp.weight}%)</th>`).join('')}
        <th>Tổng kết</th>
        <th>Trạng thái</th>
    `;
    
    const courses = semesterData.courses;
    console.log('Courses to render:', courses);
    
    courses.forEach(course => {
        const row = tbody.insertRow();
        
        // Mã lớp học
        row.insertCell().innerHTML = `<span class="course-code">${course.class_code || 'N/A'}</span>`;
        
        // Mã môn học
        row.insertCell().innerHTML = `<span class="course-code">${course.course_code || 'N/A'}</span>`;
        
        // Tên môn học
        row.insertCell().textContent = course.course_name || 'N/A';
        
        // Số tín chỉ
        row.insertCell().textContent = course.credits || 0;
        
        // Render component scores ĐỘNG
        components.forEach(comp => {
            const cell = row.insertCell();
            const score = course.components?.[comp.name] ?? '-';
            cell.textContent = score;
            cell.className = 'score-cell';
            
            // Highlight điểm thấp
            if (typeof score === 'number') {
                if (score < 5) cell.classList.add('score-red');
                else if (score < 7) cell.classList.add('score-orange');
            }
        });
        
        // Tổng kết
        const totalCell = row.insertCell();
        const totalScore = course.final_score ?? '-';
        totalCell.textContent = typeof totalScore === 'number' ? totalScore.toFixed(2) : totalScore;
        totalCell.className = 'score-cell';
        if (typeof totalScore === 'number') {
            if (totalScore < 5) totalCell.classList.add('score-red');
            else if (totalScore < 7) totalCell.classList.add('score-orange');
        }
        
        // Trạng thái
        const statusCell = row.insertCell();
        const statusBadge = document.createElement('span');
        const status = course.status || 'N/A';
        statusBadge.className = `status-badge ${
            status === 'Đạt' ? 'status-pass' : 
            status === 'Không đạt' ? 'status-fail' : 
            'status-pending'
        }`;
        // Hiển thị text phù hợp
        statusBadge.textContent = status === 'Đạt' ? 'Đạt' : 
                                   status === 'Không đạt' ? 'Không đạt' : 
                                   'Đang học';
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

    console.log('Rendering semester cards from scoreData:', scoreData);
    
    if (!scoreData || !scoreData.semesters) {
        console.error('No semester data available');
        container.innerHTML = '<p style="text-align:center;">Không có dữ liệu</p>';
        return;
    }

    // Convert semesters object to array and sort by sort_key (numeric)
    const semestersArray = Object.entries(scoreData.semesters).map(([key, data]) => ({
        key: key,
        ...data
    })).sort((a, b) => (b.sort_key || 0) - (a.sort_key || 0)); // Sort descending (newest first)

    semestersArray.forEach(semester => {
        const section = document.createElement('div');
        section.className = 'semester-section';
        
        section.innerHTML = `
            <div class="semester-header">
                <h3 class="semester-name">${semester.semester_name || semester.key}</h3>
                <p class="semester-info">GPA: ${(semester.gpa ?? 0).toFixed(2)} | Tín chỉ: ${semester.credits || 0}</p>
            </div>
            
            <table class="semester-table">
                <thead>
                    <tr>
                        <th>Mã lớp học</th>
                        <th>Mã môn học</th>
                        <th>Tên môn học</th>
                        <th>Số tín chỉ</th>
                        <th>Điểm tổng kết (Hệ 10)</th>
                        <th>Điểm chữ</th>
                        <th>Điểm hệ 4</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    ${(semester.courses || []).map(course => {
                        const finalScore = course.final_score;
                        const letterGrade = course.letter_grade || '-';
                        const status = course.status || 'N/A';
                        
                        // Chỉ hiển thị điểm khi có
                        if (finalScore === null || finalScore === undefined) {
                            return `
                            <tr>
                                <td><span class="course-code">${course.class_code || 'N/A'}</span></td>
                                <td><span class="course-code">${course.course_code || 'N/A'}</span></td>
                                <td>${course.course_name || 'N/A'}</td>
                                <td>${course.credits || 0}</td>
                                <td class="score-cell">-</td>
                                <td class="score-cell">-</td>
                                <td class="score-cell">-</td>
                                <td>
                                    <span class="status-badge status-pending">Đang học</span>
                                </td>
                            </tr>
                            `;
                        }
                        
                        const scoreClass = finalScore < 5 ? 'score-red' : finalScore < 7 ? 'score-orange' : '';
                        const grade4 = course.grade4 ?? 0;
                        
                        return `
                        <tr>
                            <td><span class="course-code">${course.class_code || 'N/A'}</span></td>
                            <td><span class="course-code">${course.course_code || 'N/A'}</span></td>
                            <td>${course.course_name || 'N/A'}</td>
                            <td>${course.credits || 0}</td>
                            <td class="score-cell ${scoreClass}">${finalScore.toFixed(2)}</td>
                            <td class="score-cell">${letterGrade}</td>
                            <td class="score-cell">${grade4.toFixed(1)}</td>
                            <td>
                                <span class="status-badge ${
                                    status === 'Đạt' ? 'status-pass' : 
                                    status === 'Không đạt' ? 'status-fail' : 
                                    'status-pending'
                                }">${status === 'Đạt' ? 'Đạt' : status === 'Không đạt' ? 'Không đạt' : 'Đang học'}</span>
                            </td>
                        </tr>
                        `;
                    }).join('')}
                </tbody>
            </table>
        `;
        
        container.appendChild(section);
    });
}

// ================= INITIALIZE =================
document.addEventListener('DOMContentLoaded', () => {
    console.log('Page loaded, initializing with scoreData:', scoreData);
    
    // Populate semester dropdown first
    const firstSemester = populateSemesterDropdown();
    
    if (firstSemester) {
        console.log('Rendering initial semester:', firstSemester);
        
        // Render initial detail table with the first (newest) semester
        renderDetailTable(firstSemester);
    } else {
        console.error('No semesters found in scoreData');
    }
    
    // Render semester cards for summary tab
    renderSemesterCards();
    
    // Summary statistics are already rendered by Blade template
    console.log('Summary data already rendered in HTML by Blade:', scoreData.summary);
});
