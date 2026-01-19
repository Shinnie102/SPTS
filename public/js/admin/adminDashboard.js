// ================= DATA CONFIGURATION =================
const dashboardData = {
    // Thống kê tổng quan
    overview: {
        totalUsers: {
            value: 2287,
            description: 'Học kì 2 năm học 2025–2026'
        },
        totalClasses: {
            value: 200,
            description: 'Thuộc 20 học phần'
        },
        warningStudents: {
            value: 287,
            description: 'Chiếm 10% tổng số sinh viên'
        },
        problemClasses: {
            value: 20,
            description: 'Chiếm 10% tổng số lớp'
        }
    },

    // Cảnh báo hệ thống
    systemAlerts: {
        totalIssues: 2, // chỉ đếm danger và warning
        alerts: [
            {
                type: 'danger', // danger, warning, info
                message: 'Grading Scheme GS002 có tổng % = 95%, không thể sử dụng',
                detail: 'Lớp: ENG202.02'
            },
            {
                type: 'warning',
                message: 'Lớp CS101.01 vượt quá sức chứa (51/50 sinh viên)',
                detail: 'Lớp: CS101.01'
            },
            {
                type: 'info',
                message: 'Kỳ 1 (2024-2025) kết thúc trong 3 ngày',
                detail: 'Chưa có kì học mới'
            },
            {
                type: 'info',
                message: 'Hệ thống sẽ bảo trì vào 15/01/2026',
                detail: 'Dự kiến từ 00:00 - 06:00'
            }
        ]
    },

    // Phân bố nguyên nhân vấn đề (dữ liệu cho biểu đồ)
    problemDistribution: {
        labels: ['Vi phạm chuyên cần', 'Vấn đề điểm'],
        values: [39.48, 60.52], // Phần trăm
        colors: ['#ef4444', '#3b82f6']
    },

    // Danh sách lớp có vấn đề
    problemClasses: [
        {
            classCode: 'CS101.01',
            courseName: 'Nhập môn Khoa học Máy tính',
            issueCount: 3,
            severity: 'Cao',
            status: 'resolved' // resolved, processing, pending
        },
        {
            classCode: 'ENG202.02',
            courseName: 'Tiếng Anh Chuyên ngành',
            issueCount: 2,
            severity: 'Trung bình',
            status: 'processing'
        },
        {
            classCode: 'MATH301.03',
            courseName: 'Toán Cao Cấp',
            issueCount: 1,
            severity: 'Thấp',
            status: 'resolved'
        },
        {
            classCode: 'PHY201.01',
            courseName: 'Vật lý Đại cương',
            issueCount: 2,
            severity: 'Cao',
            status: 'pending'
        }
    ]
};

// ================= RENDER FUNCTIONS =================

// Render thống kê tổng quan
function renderOverviewCards() {
    const { totalUsers, totalClasses, warningStudents, problemClasses } = dashboardData.overview;

    // Tổng người dùng
    document.getElementById('value-total-users').textContent = totalUsers.value.toLocaleString();
    document.getElementById('desc-total-users').textContent = totalUsers.description;

    // Tổng lớp học phần
    document.getElementById('value-total-classes').textContent = totalClasses.value.toLocaleString();
    document.getElementById('desc-total-classes').textContent = totalClasses.description;

    // Sinh viên cảnh báo
    document.getElementById('value-warning-students').textContent = warningStudents.value.toLocaleString();
    document.getElementById('desc-warning-students').textContent = warningStudents.description;

    // Lớp có vấn đề
    document.getElementById('value-problem-classes').textContent = problemClasses.value.toLocaleString();
    document.getElementById('desc-problem-classes').textContent = problemClasses.description;
}

// Render cảnh báo hệ thống
function renderSystemAlerts() {
    const { totalIssues, alerts } = dashboardData.systemAlerts;

    // Cập nhật badge số lượng vấn đề
    const badge = document.querySelector('.chart-card .badge');
    if (badge) {
        badge.textContent = `${totalIssues} vấn đề cần xử lý`;
    }

    // Render danh sách alerts
    const alertList = document.querySelector('.alert-list');
    if (!alertList) return;

    alertList.innerHTML = alerts.map(alert => `
        <div class="alert-item alert-${alert.type}">
            <p><strong>${alert.message}</strong></p>
            <span>${alert.detail}</span>
        </div>
    `).join('');
}

// Render biểu đồ phân bố nguyên nhân
function renderProblemChart() {
    const canvas = document.getElementById('problemCauseChart');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    const { labels, values, colors } = dashboardData.problemDistribution;

    // Kiểm tra xem Chart.js đã được load chưa
    if (typeof Chart === 'undefined') {
        console.warn('Chart.js chưa được load. Vui lòng thêm thư viện Chart.js');
        return;
    }

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: colors,
                borderWidth: 0,
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        font: {
                            size: 13,
                            family: 'Roboto'
                        },
                        generateLabels: function(chart) {
                            const data = chart.data;
                            return data.labels.map((label, i) => ({
                                text: `${label}: ${data.datasets[0].data[i]}%`,
                                fillStyle: data.datasets[0].backgroundColor[i],
                                hidden: false,
                                index: i
                            }));
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.label}: ${context.parsed}%`;
                        }
                    }
                }
            }
        }
    });
}

// Render bảng danh sách lớp có vấn đề
function renderProblemClassesTable() {
    const tbody = document.querySelector('.issue-table tbody');
    if (!tbody) return;

    const statusMap = {
        resolved: { text: 'Đã xử lý', class: 'status-closed' },
        processing: { text: 'Đang xử lý', class: 'status-open' },
        pending: { text: 'Chờ xử lý', class: 'status-open' }
    };

    tbody.innerHTML = dashboardData.problemClasses.map(item => {
        const status = statusMap[item.status];
        return `
            <tr>
                <td>${item.classCode}</td>
                <td>${item.courseName}</td>
                <td>${item.issueCount}</td>
                <td>${item.severity}</td>
                <td><span class="${status.class}">${status.text}</span></td>
                <td><button class="view-details-btn" data-class="${item.classCode}">Xem chi tiết</button></td>
            </tr>
        `;
    }).join('');

    // Thêm event listeners cho các nút "Xem chi tiết"
    attachViewDetailButtons();
}

// ================= EVENT HANDLERS =================

// Gắn sự kiện cho các nút "Xem chi tiết"
function attachViewDetailButtons() {
    const buttons = document.querySelectorAll('.view-details-btn');
    buttons.forEach(button => {
        button.addEventListener('click', function() {
            const classCode = this.getAttribute('data-class');
            handleViewDetails(classCode);
        });
    });
}

// Xử lý khi click "Xem chi tiết"
function handleViewDetails(classCode) {
    // TODO: Implement logic để hiển thị chi tiết lớp học
    console.log(`Xem chi tiết lớp: ${classCode}`);
    alert(`Chức năng xem chi tiết lớp ${classCode} đang được phát triển`);
}

// ================= INITIALIZATION =================

// Khởi tạo dashboard khi DOM đã load xong
document.addEventListener('DOMContentLoaded', function () {

    // 1. Ẩn dashboard lúc chưa có data
    document.body.classList.add('dashboard-loading');

    // 2. Fetch DB thật
    fetch('/admin/dashboard/api/data')
        .then(res => res.json())
        .then(data => {

            // Update data
            AdminDashboard.updateData(data);

            // Render SAU KHI CÓ DATA
            renderOverviewCards();
            renderSystemAlerts();
            renderProblemChart();
            renderProblemClassesTable();

            // 3. Hiện dashboard
            document.body.classList.remove('dashboard-loading');
        })
        .catch(err => {
            console.error('Lỗi tải dữ liệu dashboard:', err);
        });
});


// ================= UTILITY FUNCTIONS =================

// Hàm cập nhật dữ liệu dashboard (có thể gọi từ API)
function updateDashboardData(newData) {
    // Merge dữ liệu mới với dữ liệu hiện tại
    Object.assign(dashboardData, newData);

    // Re-render các phần đã thay đổi
    renderOverviewCards();
    renderSystemAlerts();
    renderProblemClassesTable();

    console.log('Dashboard data đã được cập nhật');
}

// Export functions để có thể gọi từ bên ngoài
window.AdminDashboard = {
    updateData: updateDashboardData,
    data: dashboardData
};

// ================= LOAD DATA FROM API =================

document.addEventListener('DOMContentLoaded', function () {
    fetch('/admin/dashboard/api/data')
        .then(res => res.json())
        .then(data => {
            updateDashboardData(data);
        })
        .catch(err => {
            console.error('Lỗi tải dữ liệu dashboard:', err);
        });
});

