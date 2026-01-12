// render.js
document.addEventListener('DOMContentLoaded', function() {
  // 1. Render các card thống kê
  renderStats();
  
  // 2. Render bảng lớp học phần (chỉ 3 lớp đầu)
  renderClassTable();
  
  // 3. Render biểu đồ
  renderChart();
  
  // 4. Render danh sách cảnh báo (chỉ 4 sinh viên đầu)
  renderWarnings();
});

function renderStats() {
  const stats = mockData.stats;
  
  const statElements = {
    'totalClasses': stats.totalClasses,
    'warnings': stats.warnings,
    'completedGrading': stats.completedGrading,
    'pendingGrading': stats.pendingGrading
  };
  
  Object.keys(statElements).forEach(key => {
    const element = document.querySelector(`[data-stat="${key}"]`);
    if (element) {
      element.textContent = statElements[key];
    }
  });
}

function renderClassTable() {
  const tableBody = document.getElementById('class-table-body');
  if (!tableBody) return;
  
  tableBody.innerHTML = '';
  
  // Chỉ lấy 3 lớp đầu tiên
  const classesToShow = mockData.classes.slice(0, 3);
  
  classesToShow.forEach(cls => {
    const row = document.createElement('tr');
    row.setAttribute('role', 'row');
    
    row.innerHTML = `
      <td role="cell">${cls.code}</td>
      <td role="cell">${cls.name}</td>
      <td role="cell">${cls.total}</td>
      <td role="cell"><span class="status ${cls.status}">${cls.statusText}</span></td>
      <td role="cell"><a href="DSLopPhuTrach.html?class=${cls.code}" class="action-link">Xem chi tiết →</a></td>
    `;
    
    tableBody.appendChild(row);
  });
}

function renderChart() {
  const chartBars = document.getElementById('chart-bars');
  if (!chartBars) return;
  
  chartBars.innerHTML = '';

  const chartLimit = 100; 
  
  mockData.chartData.forEach((item, index) => {
    const bar = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
    
    const heightPercentage = (item.students / chartLimit) * 100;
    
    const x = 2 + (index * 17);
    const y = 100 - heightPercentage;
    
    bar.setAttribute('class', 'bar-item');
    bar.setAttribute('x', `${x}%`);
    bar.setAttribute('y', `${y}%`);
    bar.setAttribute('width', '12%');
    bar.setAttribute('height', `${heightPercentage}%`);
    bar.setAttribute('fill', '#0088f0');
    bar.setAttribute('data-value', item.students);
    bar.setAttribute('aria-label', `${item.range}: ${item.students} sinh viên`);
    
    chartBars.appendChild(bar);
  });
}

function renderWarnings() {
  const warningsList = document.getElementById('warnings-list');
  if (!warningsList) return;
  
  warningsList.innerHTML = '';
  
  // Chỉ lấy 4 cảnh báo đầu tiên
  const warningsToShow = mockData.warnings.slice(0, 4);
  
  warningsToShow.forEach(warning => {
    const warningItem = document.createElement('article');
    warningItem.className = 'warning-item';
    
    warningItem.innerHTML = `
      <img class="warning-icon" src="img/vector-8.svg" alt="Cảnh báo icon" />
      <div class="warning-content">
        <h4 class="warning-student">${warning.student} • ${warning.id} • ${warning.classCode}</h4>
        <p class="warning-reason">${warning.reason}</p>
      </div>
    `;
    
    warningsList.appendChild(warningItem);
  });
}