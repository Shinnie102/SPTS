// render.js
document.addEventListener('DOMContentLoaded', function() {
  loadDashboardData();
});

async function loadDashboardData() {
  const config = window.LecturerDashboardConfig || {};
  const apiUrl = config.apiUrl;

  if (!apiUrl) {
    // No config => nothing to fetch
    return;
  }

  try {
    const resp = await fetch(apiUrl, {
      method: 'GET',
      headers: { 'Accept': 'application/json' }
    });

    if (!resp.ok) {
      return;
    }

    const payload = await resp.json();

    if (payload && payload.stats) {
      renderStats(payload.stats);
    }

    renderChart(payload.chartData || [], payload.totalStudents || 0);
    renderWarnings(payload.warnings || [], config.warningIconUrl);
  } catch (e) {
    // Silent fail to avoid breaking the dashboard UI
  }
}

function renderStats(stats) {
  const statElements = {
    'totalClasses': stats.totalClasses,
    'warnings': stats.warnings,
    'completedGrading': stats.completedGrading,
    'pendingGrading': stats.pendingGrading
  };

  Object.keys(statElements).forEach(key => {
    const element = document.querySelector(`[data-stat="${key}"]`);
    if (element && statElements[key] !== undefined && statElements[key] !== null) {
      element.textContent = statElements[key];
    }
  });
}

function renderChart(chartData, totalStudents) {
  const chartBars = document.getElementById('chart-bars');
  if (!chartBars) return;

  const totalStudentsEl = document.getElementById('score-total-students');
  
  // Match Report page semantics: Y-axis shows student counts based on totalStudents
  const maxStudents = Math.max(0, parseInt(totalStudents || 0, 10));

  if (totalStudentsEl) {
    totalStudentsEl.textContent = String(maxStudents);
  }

  const yAxis = chartBars.closest('.main-chart') ? chartBars.closest('.main-chart').querySelector('.y-axis-left') : document.querySelector('.y-axis-left');
  if (yAxis) {
    const labels = yAxis.querySelectorAll('span');
    const values = [
      String(maxStudents),
      String(Math.ceil(maxStudents * 0.8)),
      String(Math.ceil(maxStudents * 0.6)),
      String(Math.ceil(maxStudents * 0.4)),
      String(Math.ceil(maxStudents * 0.2)),
      '0'
    ];

    if (labels && labels.length >= values.length) {
      values.forEach((v, i) => {
        labels[i].textContent = v;
      });
    }
  }

  chartBars.innerHTML = '';

  const safeTotal = Math.max(1, maxStudents);

  (chartData || []).forEach((item, index) => {
    const bar = document.createElementNS('http://www.w3.org/2000/svg', 'rect');

    const students = Number(item.students || 0);
    const clampedStudents = Math.max(0, Math.min(safeTotal, students));
    const heightPercentage = (clampedStudents / safeTotal) * 100;
    
    const x = 2 + (index * 17);
    const y = 100 - heightPercentage;
    
    bar.setAttribute('class', 'bar-item');
    bar.setAttribute('x', `${x}%`);
    bar.setAttribute('y', `${y}%`);
    bar.setAttribute('width', '12%');
    bar.setAttribute('height', `${heightPercentage}%`);
    bar.setAttribute('fill', '#0088f0');
    bar.setAttribute('data-value', students);
    bar.setAttribute('aria-label', `${item.range}: ${students} sinh viên`);

    const title = document.createElementNS('http://www.w3.org/2000/svg', 'title');
    title.textContent = `${students} sinh viên`;
    bar.appendChild(title);
    
    chartBars.appendChild(bar);
  });
}

function renderWarnings(warnings, warningIconUrl) {
  const warningsList = document.getElementById('warnings-list');
  if (!warningsList) return;
  
  warningsList.innerHTML = '';
  
  // Chỉ lấy 4 cảnh báo đầu tiên
  const warningsToShow = (warnings || []).slice(0, 4);
  
  warningsToShow.forEach(warning => {
    const warningItem = document.createElement('article');
    warningItem.className = 'warning-item';

    const iconUrl = warningIconUrl || '/lecturer/img/vector-8.svg';
    
    warningItem.innerHTML = `
      <img class="warning-icon" src="${iconUrl}" alt="Cảnh báo icon" />
      <div class="warning-content">
        <h4 class="warning-student">${warning.student} • ${warning.id} • ${warning.classCode}</h4>
        <p class="warning-reason">${warning.reason}</p>
      </div>
    `;
    
    warningsList.appendChild(warningItem);
  });
}