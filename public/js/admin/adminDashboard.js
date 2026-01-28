// ================= GLOBAL STATE =================
let dashboardData = {
    cards: {},
    problemClassesList: [],
    problemCauses: {}
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

// ================= RENDER ALERTS =================
function renderSystemAlerts() {
    const container = document.querySelector('.alert-list');
    if (!container) return;

    container.innerHTML = `
        <div class="alert-item alert-success">
            <p><strong>Dashboard hoạt động</strong></p>
            <span>Dữ liệu đã load thành công</span>
        </div>
    `;
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
            plugins: {
                legend: { position: 'bottom' }
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
                <td colspan="6" style="text-align:center">
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
}

// ================= LOAD DATA =================
document.addEventListener('DOMContentLoaded', () => {
    fetch('/admin/dashboard/api/data')
        .then(res => res.json())
        .then(data => {
            console.log('✅ Dashboard API:', data);

            dashboardData.cards = data.cards || {};
            dashboardData.problemClassesList = data.problemClassesList || [];
            dashboardData.problemCauses = data.problemCauses || {};

            renderOverviewCards();
            renderSystemAlerts();
            renderProblemChart();
            renderProblemClassesTable();
        })
        .catch(err => {
            console.error('❌ Lỗi tải dashboard:', err);
        });
});
