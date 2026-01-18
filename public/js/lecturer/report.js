// report.js
// Report page only (Lecturer): fetch + DOM rendering. No mock data, no SQL in JS.

(() => {
  const REPORT_PATH_RE = /^\/lecturer\/class\/(\d+)\/report\/?$/;

  const initializeHeaderClassDropdown = () => {
    const classSelect = document.getElementById('class-select');
    if (!classSelect) return;

    const wrapper = classSelect.closest('.select-wrapper');
    if (!wrapper) return;

    // If native select is visible/usable or already initialized elsewhere, do nothing.
    if (wrapper.querySelector('.select-trigger') || wrapper.querySelector('.session-menu')) {
      return;
    }

    const options = Array.from(classSelect.querySelectorAll('option'));
    const selectedIndex = Math.max(0, options.findIndex(o => o.selected));
    const selectedOption = options[selectedIndex] || options[0] || null;

    const labelEl = document.querySelector('label[for="class-select"]');
    const labelText = labelEl ? (labelEl.textContent || '').trim() : 'Lớp học phần';

    const trigger = document.createElement('div');
    trigger.className = 'select-trigger';
    trigger.innerHTML = `<span class="current-text">${selectedOption ? selectedOption.text : 'Chưa có lựa chọn'}</span><div class="select-arrow">▼</div>`;
    wrapper.appendChild(trigger);

    const menu = document.createElement('div');
    menu.className = 'session-menu';
    menu.innerHTML = `
      <h3 class="menu-title">${labelText}</h3>
      <div class="search-box-container">
        <input type="text" class="search-field" placeholder="Nhập nội dung cần tìm....">
      </div>
      <ul class="menu-list">
        ${options.map((opt, index) => `
          <li class="menu-item ${index === selectedIndex ? 'active' : ''}" data-value="${String(opt.value)}">${opt.text}</li>
        `).join('')}
      </ul>
    `;
    wrapper.appendChild(menu);

    const searchField = menu.querySelector('.search-field');
    const menuItems = Array.from(menu.querySelectorAll('.menu-item'));

    trigger.addEventListener('click', (e) => {
      e.stopPropagation();
      wrapper.classList.toggle('active-menu');
    });

    menuItems.forEach((item) => {
      item.addEventListener('click', () => {
        const currentActive = menu.querySelector('.menu-item.active');
        if (currentActive) currentActive.classList.remove('active');
        item.classList.add('active');

        trigger.querySelector('.current-text').textContent = item.textContent;
        classSelect.value = item.getAttribute('data-value') || '';
        classSelect.dispatchEvent(new Event('change'));

        wrapper.classList.remove('active-menu');
      });
    });

    if (searchField) {
      searchField.addEventListener('input', (e) => {
        const filter = (e.target.value || '').toLowerCase();
        menuItems.forEach((item) => {
          const text = (item.textContent || '').toLowerCase();
          item.style.display = text.includes(filter) ? 'block' : 'none';
        });
      });
    }

    if (!document.body.dataset.reportDropdownCloseWired) {
      document.body.dataset.reportDropdownCloseWired = '1';
      document.addEventListener('click', () => {
        wrapper.classList.remove('active-menu');
      });
    }
  };

  const getClassIdFromUrl = () => {
    const path = window.location.pathname || '';
    const match = path.match(REPORT_PATH_RE);
    return match ? parseInt(match[1], 10) : null;
  };

  const fetchJson = async (url, options = {}) => {
    const response = await fetch(url, {
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        ...(options.headers || {})
      },
      ...options
    });

    const contentType = response.headers.get('content-type') || '';
    const data = contentType.includes('application/json') ? await response.json() : null;

    if (!response.ok) {
      return {
        success: false,
        message: (data && data.message) ? data.message : `HTTP ${response.status}`,
        status: response.status
      };
    }

    return data;
  };

  const ensureTooltip = () => {
    let el = document.getElementById('report-tooltip');
    if (el) return el;

    el = document.createElement('div');
    el.id = 'report-tooltip';
    el.style.position = 'fixed';
    el.style.zIndex = '9999';
    el.style.pointerEvents = 'none';
    el.style.padding = '6px 10px';
    el.style.borderRadius = '8px';
    el.style.background = 'rgba(0,0,0,0.8)';
    el.style.color = '#fff';
    el.style.fontSize = '12px';
    el.style.lineHeight = '1.2';
    el.style.boxShadow = '0 6px 18px rgba(0,0,0,0.25)';
    el.style.display = 'none';
    document.body.appendChild(el);
    return el;
  };

  const showTooltip = (text, clientX, clientY) => {
    const el = ensureTooltip();
    el.textContent = text;
    const offsetX = 12;
    const offsetY = 12;
    el.style.left = `${Math.min(window.innerWidth - 10, clientX + offsetX)}px`;
    el.style.top = `${Math.min(window.innerHeight - 10, clientY + offsetY)}px`;
    el.style.display = 'block';
  };

  const hideTooltip = () => {
    const el = document.getElementById('report-tooltip');
    if (!el) return;
    el.style.display = 'none';
  };

  const renderScoreDistribution = (dist, message, totalStudents) => {
    const chartBars = document.getElementById('chart-bars');
    if (!chartBars) return;

    // Update Y-axis labels from backend total_students
    const yAxis = document.querySelector('.y-axis-left');
    if (yAxis) {
      const labels = yAxis.querySelectorAll('span');
      const maxStudents = Math.max(0, parseInt(totalStudents || 0, 10));
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

    const ranges = [
      { key: '9_10', label: '9-10' },
      { key: '8_8_9', label: '8-8.9' },
      { key: '7_7_9', label: '7-7.9' },
      { key: '6_6_9', label: '6-6.9' },
      { key: '5_5_9', label: '5-5.9' },
      { key: 'below_5', label: '<5' }
    ];

    if (!dist) {
      const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
      text.setAttribute('x', '50');
      text.setAttribute('y', '50');
      text.setAttribute('text-anchor', 'middle');
      text.setAttribute('dominant-baseline', 'middle');
      text.setAttribute('fill', '#666');
      text.textContent = message || 'Chưa có dữ liệu';
      chartBars.appendChild(text);
      return;
    }

    const values = ranges.map(r => parseInt(dist[r.key] || 0, 10));
    const totalCount = values.reduce((a, b) => a + b, 0);
    if (totalCount === 0) {
      const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
      text.setAttribute('x', '50');
      text.setAttribute('y', '50');
      text.setAttribute('text-anchor', 'middle');
      text.setAttribute('dominant-baseline', 'middle');
      text.setAttribute('fill', '#666');
      text.textContent = message || 'Chưa có dữ liệu';
      chartBars.appendChild(text);
      return;
    }

    const maxStudents = Math.max(1, parseInt(totalStudents || 0, 10));

    values.forEach((val, index) => {
      const bar = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
      const clampedVal = Math.max(0, Math.min(maxStudents, val));
      const heightPercentage = (clampedVal / maxStudents) * 100;
      const x = 2 + (index * 17);
      const y = 100 - heightPercentage;

      bar.setAttribute('class', 'bar-item');
      bar.setAttribute('x', `${x}%`);
      bar.setAttribute('y', `${y}%`);
      bar.setAttribute('width', '12%');
      bar.setAttribute('height', `${heightPercentage}%`);
      bar.setAttribute('fill', '#0088f0');
      bar.setAttribute('data-value', String(val));
      bar.setAttribute('aria-label', `${ranges[index].label}: ${val} sinh viên`);

      const title = document.createElementNS('http://www.w3.org/2000/svg', 'title');
      title.textContent = `${val} sinh viên`;
      bar.appendChild(title);

      bar.addEventListener('mousemove', (e) => {
        showTooltip(`${val} sinh viên`, e.clientX, e.clientY);
      });
      bar.addEventListener('mouseleave', () => hideTooltip());

      chartBars.appendChild(bar);
    });
  };

  const renderPassFail = (ratio) => {
    const donutSegment = document.querySelector('.donut-segment');
    const chartNumber = document.querySelector('.chart-number');
    const donutRing = document.querySelector('.donut-ring');

    if (!donutSegment || !chartNumber) return;

    if (!ratio) {
      donutSegment.setAttribute('stroke-dasharray', '0 100');
      chartNumber.textContent = '';
      donutSegment.onmousemove = null;
      donutSegment.onmouseleave = null;
      if (donutRing) {
        donutRing.onmousemove = null;
        donutRing.onmouseleave = null;
      }
      return;
    }

    const pass = parseInt(ratio.pass || 0, 10);
    const fail = parseInt(ratio.fail || 0, 10);
    const total = pass + fail;

    const passPct = total > 0 ? Math.round((pass / total) * 100) : 0;

    donutSegment.setAttribute('stroke-dasharray', `${passPct} ${100 - passPct}`);
    chartNumber.textContent = String(passPct);

    donutSegment.onmousemove = (e) => showTooltip(`${pass} sinh viên đạt`, e.clientX, e.clientY);
    donutSegment.onmouseleave = () => hideTooltip();

    if (donutRing) {
      donutRing.onmousemove = (e) => showTooltip(`${fail} sinh viên rớt`, e.clientX, e.clientY);
      donutRing.onmouseleave = () => hideTooltip();
    }
  };

  const wireInlineDetailToggle = (tbody) => {
    if (!tbody || tbody.dataset.detailsWired === '1') return;
    tbody.dataset.detailsWired = '1';

    tbody.addEventListener('click', async (e) => {
      const link = e.target && e.target.closest ? e.target.closest('a.view-detail') : null;
      if (!link) return;
      e.preventDefault();

      const row = link.closest('tr');
      if (!row) return;

      const next = row.nextElementSibling;
      const hasDetailRow = next && next.classList && next.classList.contains('inline-detail-row');

      if (hasDetailRow) {
        const isHidden = next.style.display === 'none' || next.hasAttribute('hidden');
        if (isHidden) {
          next.style.display = '';
          next.removeAttribute('hidden');
          link.setAttribute('aria-expanded', 'true');
        } else {
          next.style.display = 'none';
          next.setAttribute('hidden', 'hidden');
          link.setAttribute('aria-expanded', 'false');
        }
        return;
      }

      // Placeholder only (detail view not implemented)
      link.setAttribute('aria-busy', 'true');
      link.style.pointerEvents = 'none';

      try {
        const detailTr = document.createElement('tr');
        detailTr.className = 'inline-detail-row';
        detailTr.innerHTML = `
          <td colspan="6" style="padding: 12px 16px; background: rgba(0,0,0,0.02);">
            <div class="inline-detail-content">Chưa có dữ liệu chi tiết</div>
          </td>
        `;
        row.insertAdjacentElement('afterend', detailTr);
        link.setAttribute('aria-expanded', 'true');
      } finally {
        link.setAttribute('aria-busy', 'false');
        link.style.pointerEvents = '';
      }
    });
  };

  const renderAcademicWarnings = (warnings, message) => {
    const table = document.querySelector('.styled-table');
    if (!table) return;
    const tbody = table.querySelector('tbody');
    if (!tbody) return;

    tbody.innerHTML = '';

    const rows = Array.isArray(warnings) ? warnings : [];

    if (rows.length === 0) {
      const tr = document.createElement('tr');
      tr.innerHTML = `<td colspan="6" style="text-align:center; padding: 16px;">${message || 'Chưa có dữ liệu'}</td>`;
      tbody.appendChild(tr);
      return;
    }

    rows.forEach((w) => {
      const numericTotal = Number(w.total_score);
      const total = Number.isFinite(numericTotal) ? numericTotal.toFixed(1) : '';

      const scoreClass = (numericTotal < 5) ? 'score-danger' : 'score-warning';
      const statusClass = (numericTotal < 5) ? 'status-danger' : 'status-warning';

      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td class="bold-text">${w.student_code || ''}</td>
        <td>${w.full_name || ''}</td>
        <td>${w.class_code || ''}</td>
        <td class="${scoreClass}">${total}</td>
        <td class="${statusClass}">${w.status || ''}</td>
        <td><a href="#" class="view-detail" data-student-id="${w.student_id || ''}"><i class="fas fa-eye"></i> Xem chi tiết</a></td>
      `;
      tbody.appendChild(tr);
    });

    wireInlineDetailToggle(tbody);
  };

  const renderEmptyState = (message) => {
    renderScoreDistribution(null, message, 0);
    renderPassFail(null);
    renderAcademicWarnings([], message);
  };

  const loadAndRenderReport = async () => {
    const classId = getClassIdFromUrl();
    if (!classId) return;

    const result = await fetchJson(`/lecturer/class/${classId}/report-data`);
    if (result && result.success === false) {
      renderEmptyState(result.message || 'Chưa có dữ liệu');
      return;
    }

    const totalStudents = Number.isFinite(Number(result.total_students)) ? Number(result.total_students) : 0;
    renderScoreDistribution(result.score_distribution || null, undefined, totalStudents);
    renderPassFail(result.pass_fail_ratio || null);
    renderAcademicWarnings(result.academic_warnings || [], undefined);
  };

  document.addEventListener('DOMContentLoaded', () => {
    const path = window.location.pathname || '';
    if (!REPORT_PATH_RE.test(path)) return;

    // Header dropdown on Report page (Report uses only report.js)
    initializeHeaderClassDropdown();
    loadAndRenderReport();
  });
})();
