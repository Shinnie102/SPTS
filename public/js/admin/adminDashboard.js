// ================= GLOBAL STATE =================
let dashboardData = {
    cards: {},
    problemClassesList: [],
    problemCauses: {},
    systemWarnings: [],
    statistics: {}
};

// ================= RENDER OVERVIEW =================
function renderOverviewCards() {
    const c = dashboardData.cards;

    const set = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.textContent = value ?? '-';
    };

    set('value-total-users', c.totalUsers);
    set('value-total-classes', c.totalClasses);
    set('value-warning-students', c.warningStudents);
    set('value-problem-classes', c.problemClasses);
}

// ================= RENDER ALERTS VỚI DỮ LIỆU MẪU =================
function renderSystemAlerts() {
    const container = document.querySelector('.alert-list');
    if (!container) return;

    let warnings = dashboardData.systemWarnings || [];

    // Nếu API không trả về warnings, dùng dữ liệu mẫu
    if (warnings.length === 0 && dashboardData.cards.problemClasses > 0) {
        const problemClasses = dashboardData.cards.problemClasses || 15;
        const warningStudents = dashboardData.cards.warningStudents || 185;
        const totalClasses = dashboardData.cards.totalClasses || 44;
        const totalUsers = dashboardData.cards.totalUsers || 56;

        const classPercentage = ((problemClasses / totalClasses) * 100).toFixed(1);
        const studentPercentage = ((warningStudents / totalUsers) * 100).toFixed(1);
        const totalIssues = problemClasses + warningStudents;

        warnings = [
            {
                type: 'critical',
                icon: '🚨',
                title: 'Tỷ lệ lớp có vấn đề cao',
                message: `${classPercentage}% tổng số lớp đang có vấn đề cần xử lý khẩn cấp`,
            },
            {
                type: 'error',
                icon: '📚',
                title: 'Lớp học chưa có buổi học',
                message: `Có ${problemClasses} lớp học phần chưa có buổi học nào được lên lịch`,
            },
            {
                type: 'warning',
                icon: '⚠️',
                title: 'Sinh viên có vấn đề',
                message: `Có ${warningStudents} sinh viên đang trong trạng thái cảnh báo học vụ`,
            },
            {
                type: 'critical',
                icon: '🔴',
                title: 'Tỷ lệ sinh viên cảnh báo cao',
                message: `${studentPercentage}% sinh viên đang trong tình trạng học vụ không tốt`,
            },
            {
                type: 'info',
                icon: 'ℹ️',
                title: 'Tổng quan vấn đề',
                message: `Hệ thống phát hiện tổng cộng ${totalIssues} vấn đề cần được xử lý`,
            }
        ];
    }

    // Nếu không có vấn đề gì
    if (warnings.length === 0) {
        container.innerHTML = `
            <div class="alert-item alert-success">
                <p><strong>✅ Hệ thống hoạt động bình thường</strong></p>
                <span>Không có vấn đề nào cần xử lý</span>
            </div>
        `;
        return;
    }

    // Hiển thị warnings
    container.innerHTML = warnings.map(warning => {
        let alertClass = 'alert-info';
        if (warning.type === 'critical') alertClass = 'alert-critical';
        else if (warning.type === 'error') alertClass = 'alert-danger';
        else if (warning.type === 'warning') alertClass = 'alert-warning';

        return `
            <div class="alert-item ${alertClass}">
                <p><strong>${warning.icon} ${warning.title}</strong></p>
                <span>${warning.message}</span>
            </div>
        `;
    }).join('');
}

// ================= RENDER CHART =================
let problemChart = null;

function renderProblemChart() {
    if (typeof Chart === 'undefined') {
        console.warn('❌ Chart.js chưa được load');
        return;
    }

    const canvas = document.getElementById('problemCauseChart');
    if (!canvas) return;

    const labels = Object.keys(dashboardData.problemCauses);
    const values = Object.values(dashboardData.problemCauses);

    if (problemChart) problemChart.destroy();

    problemChart = new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: ['#ef4444', '#f59e0b', '#3b82f6']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        font: { size: 12 }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

// ================= RENDER TABLE =================
function renderProblemClassesTable() {
    const tbody = document.querySelector('.issue-table tbody');
    if (!tbody) return;

    if (!dashboardData.problemClassesList.length) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" style="text-align:center; padding: 40px; color: #64748b;">
                    Không có dữ liệu
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = dashboardData.problemClassesList.map(item => `
        <tr>
            <td>${item.class_code}</td>
            <td>${item.course_name}</td>
            <td>${item.problem_count}</td>
            <td>Cảnh báo</td>
            <td><span class="status-open">Chưa xử lý</span></td>
            <td>
                <button class="view-details-btn" data-code="${item.class_code}">
                    Xem chi tiết
                </button>
            </td>
        </tr>
    `).join('');

    // Thêm event listener cho các nút "Xem chi tiết"
    document.querySelectorAll('.view-details-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const classCode = this.getAttribute('data-code');
            console.log('Xem chi tiết lớp:', classCode);
            // Thêm logic xử lý tại đây
            alert(`Xem chi tiết lớp: ${classCode}`);
        });
    });
}

// ================= RENDER STATISTICS INFO =================
function renderStatisticsInfo() {
    const stats = dashboardData.statistics;
    if (!stats) return;

    console.log('📊 Thống kê chi tiết:');
    console.log(`   - Tỷ lệ lớp có vấn đề: ${stats.classWarningPercentage}%`);
    console.log(`   - Tỷ lệ sinh viên cảnh báo: ${stats.studentWarningPercentage}%`);
    console.log(`   - Tổng số vấn đề: ${stats.totalIssues}`);
}

// ================= LOAD DATA =================
document.addEventListener('DOMContentLoaded', () => {
    console.log('🔄 Đang tải dữ liệu dashboard...');

    fetch('/admin/dashboard/api/data')
        .then(res => {
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            return res.json();
        })
        .then(data => {
            console.log('✅ Dashboard API Response:', data);

            if (data.error) {
                console.error('❌ API trả về lỗi:', data.message);

                // Hiển thị thông báo lỗi
                const container = document.querySelector('.alert-list');
                if (container) {
                    container.innerHTML = `
                        <div class="alert-item alert-danger">
                            <p><strong>⚠️ Lỗi tải dữ liệu</strong></p>
                            <span>${data.message}</span>
                        </div>
                    `;
                }
                return;
            }

            // Lưu dữ liệu vào state
            dashboardData.cards = data.cards || {};
            dashboardData.problemClassesList = data.problemClassesList || [];
            dashboardData.problemCauses = data.problemCauses || {};
            dashboardData.systemWarnings = data.systemWarnings || [];
            dashboardData.statistics = data.statistics || {};

            // Render các phần
            renderOverviewCards();
            renderSystemAlerts();
            renderProblemChart();
            renderProblemClassesTable();
            renderStatisticsInfo();
        })
        .catch(err => {
            console.error('❌ Lỗi tải dashboard:', err);

            // Hiển thị thông báo lỗi cho người dùng
            const container = document.querySelector('.alert-list');
            if (container) {
                container.innerHTML = `
                    <div class="alert-item alert-danger">
                        <p><strong>⚠️ Lỗi kết nối</strong></p>
                        <span>Không thể kết nối đến server. Vui lòng kiểm tra kết nối và thử lại.</span>
                    </div>
                `;
            }
        });
});

// ================= AUTO REFRESH (TÙY CHỌN) =================
// Bỏ comment dòng dưới để tự động refresh mỗi 5 phút
/*
setInterval(() => {
    console.log('🔄 Đang làm mới dữ liệu...');
    fetch('/admin/dashboard/api/data')
        .then(res => res.json())
        .then(data => {
            if (!data.error) {
                dashboardData.cards = data.cards || {};
                dashboardData.problemClassesList = data.problemClassesList || [];
                dashboardData.problemCauses = data.problemCauses || {};
                dashboardData.systemWarnings = data.systemWarnings || [];
                dashboardData.statistics = data.statistics || {};

                renderOverviewCards();
                renderSystemAlerts();
                renderProblemChart();
                renderProblemClassesTable();
                renderStatisticsInfo();

                console.log('✅ Dữ liệu đã được làm mới');
            }
        })
        .catch(err => console.error('❌ Lỗi làm mới:', err));
}, 300000); // 5 phút = 300000ms
*/
