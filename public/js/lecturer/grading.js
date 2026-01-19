// grading.js (Lecturer) - component_id driven grading UI.
// Requirements:
// - No hard-coded attendance/midterm/final logic
// - All mapping is enrollment_id + component_id
// - Accept score 0 / 0.0
// - Render columns by grading_component order

(() => {
  // DOM Elements
  const structureTable = document.getElementById('structureTable');
  const gradeTable = document.getElementById('gradeTable');

  const clampScore = (n) => Math.max(0, Math.min(10, n));

  const parseScoreValue = (raw) => {
    const s = (raw ?? '').toString().trim();
    if (s === '') return null;
    const n = parseFloat(s);
    if (!Number.isFinite(n) || Number.isNaN(n)) return null;
    return clampScore(n);
  };

  const format1 = (n) => (Number.isFinite(n) ? n.toFixed(1) : '_');

  // IMPORTANT: Must match PHP round($score, 1, PHP_ROUND_HALF_UP)
  // Test cases:
  // 3.96 -> 4.0
  // 3.94 -> 3.9
  const roundScore1 = (n) => {
    if (n === null || n === undefined) return null;
    if (!Number.isFinite(n) || Number.isNaN(n)) return null;
    // Avoid floating quirks (e.g., 1.005)
    return Math.round((n + 1e-9) * 10) / 10;
  };

  const getStatusTooltip = (status, rawTotal, roundedTotal) => {
    if (roundedTotal === null || roundedTotal === undefined || !Number.isFinite(roundedTotal)) {
      return 'Chưa đủ điểm để tính tổng kết.';
    }
    const rawText = (rawTotal !== null && rawTotal !== undefined && Number.isFinite(rawTotal))
      ? `Raw ${rawTotal.toFixed(2)} → Làm tròn ${roundedTotal.toFixed(1)}.`
      : `Làm tròn ${roundedTotal.toFixed(1)}.`;
    if (status === 'passed') return `${rawText} Đạt (>= 5.0)`;
    if (status === 'warning') return `${rawText} Nguy cơ (>= 4.0 và < 5.0)`;
    if (status === 'failed') return `${rawText} Không đạt (< 4.0)`;
    return '';
  };

  const applyTotalCellDecorations = (cellEl, rawTotal, roundedTotal, status) => {
    if (!cellEl) return;
    cellEl.classList.remove('grade-total--passed', 'grade-total--warning', 'grade-total--failed', 'grade-total--empty');
    const cls = status ? `grade-total--${status}` : 'grade-total--empty';
    cellEl.classList.add('grade-total', cls);
    const tip = getStatusTooltip(status, rawTotal, roundedTotal);
    if (tip) cellEl.setAttribute('title', tip);
  };

  const TopWarning = (() => {
    let timer = null;

    const show = ({ heading, detail, autoHideMs = 3500 } = {}) => {
      const lockBox = document.getElementById('lock-notification');
      if (!lockBox) {
        if (detail) alert(detail);
        return;
      }

      const h = lockBox.querySelector('.warning-heading');
      const d = lockBox.querySelector('.warning-detail');

      if (h && heading) h.textContent = heading;
      if (d && detail) d.textContent = detail;

      lockBox.style.display = 'block';
      window.scrollTo({ top: 0, behavior: 'smooth' });

      if (timer) window.clearTimeout(timer);
      if (autoHideMs && autoHideMs > 0) {
        timer = window.setTimeout(() => {
          lockBox.style.display = 'none';
        }, autoHideMs);
      }
    };

    return { show };
  })();

  const setButtonLoading = (buttonEl, isLoading, loadingText = 'Đang lưu...') => {
    if (!buttonEl) return;
    if (buttonEl.dataset.origText === undefined) {
      buttonEl.dataset.origText = buttonEl.innerText;
    }
    buttonEl.disabled = !!isLoading;
    buttonEl.style.opacity = isLoading ? '0.6' : '1';
    buttonEl.style.cursor = isLoading ? 'not-allowed' : 'pointer';
    buttonEl.innerText = isLoading ? loadingText : (buttonEl.dataset.origText || 'Lưu');
  };

  // State
  const AppState = {
    components: [], // [{component_id, component_name, weight_percent, order_no}]
    structureDirty: false,
    students: [], // [{ enrollment_id, full_name, student_code }]
    filteredStudents: [],
    scores: new Map(), // key `${enrollment_id}:${component_id}` => number|null
    savingStructure: false,
    savingGrades: false
  };

  const getWeightPercent = (component) => {
    // API may return weight (0..1) OR weight_percent. Support both without guessing schema.
    if (component && typeof component.weight_percent === 'number') return component.weight_percent;
    if (component && component.weight_percent != null && !Number.isNaN(Number(component.weight_percent))) return Number(component.weight_percent);
    if (component && typeof component.weight === 'number') return component.weight * 100;
    if (component && component.weight != null && !Number.isNaN(Number(component.weight))) return Number(component.weight) * 100;
    return 0;
  };

  const getWeightRatio = (component) => (getWeightPercent(component) / 100);

  const weightedComponents = () => AppState.components.filter(c => getWeightRatio(c) > 0);

  const getScoreKey = (enrollmentId, componentId) => `${enrollmentId}:${componentId}`;

  const getScore = (enrollmentId, componentId) => {
    const key = getScoreKey(enrollmentId, componentId);
    return AppState.scores.has(key) ? AppState.scores.get(key) : null;
  };

  const setScore = (enrollmentId, componentId, value) => {
    AppState.scores.set(getScoreKey(enrollmentId, componentId), value);
  };

  const computeTotalRaw = (enrollmentId) => {
    const comps = weightedComponents();
    if (comps.length === 0) return null;

    let sum = 0;
    for (const c of comps) {
      const v = getScore(enrollmentId, c.component_id);
      if (v === null || v === undefined) return null;
      sum += (v * getWeightRatio(c));
    }
    return sum;
  };

  const computeTotalRounded = (enrollmentId) => {
    const raw = computeTotalRaw(enrollmentId);
    if (raw === null || raw === undefined || !Number.isFinite(raw)) return null;
    return roundScore1(raw);
  };

  const computeStatus = (roundedTotal) => {
    // IMPORTANT: status is based on rounded total (1 decimal)
    if (roundedTotal === null || roundedTotal === undefined || !Number.isFinite(roundedTotal)) return '';
    if (roundedTotal >= 5.0) return 'passed';
    if (roundedTotal >= 4.0) return 'warning';
    return 'failed';
  };

  // Templates
  const Templates = {
    getStatusBadge: (status) => {
      const statusMap = {
        passed: { text: 'Đạt', class: 'status-passed' },
        failed: { text: 'Không đạt', class: 'status-failed' },
        warning: { text: 'Nguy cơ', class: 'status-warning' },
        '': { text: 'Chưa có', class: 'status-empty' }
      };
      const statusInfo = statusMap[status] || statusMap[''];
      return `<span class="status-badge ${statusInfo.class}">${statusInfo.text}</span>`;
    },

    structureTable: (components) => {
      return `
        <div class="structure-head" role="rowgroup">
          <div class="structure-col" role="columnheader">Thành phần điểm</div>
          <div class="structure-col" role="columnheader">Tỉ trọng (%)</div>
        </div>
        <div class="structure-body" role="rowgroup">
          ${components.map(c => `
            <div class="structure-row" role="row" data-component-id="${c.component_id}">
              <div class="structure-cell" role="cell">${c.component_name}</div>
              <div class="structure-cell" role="cell">
                <input type="number"
                       class="structure-input"
                       value="${Math.round(getWeightPercent(c))}"
                       min="0"
                       max="100"
                       step="1"
                       data-component-id="${c.component_id}">
              </div>
            </div>
          `).join('')}
        </div>
        <div class="structure-footer">
          <button class="save-structure-btn" type="button" id="saveStructureBtn">Lưu</button>
        </div>
      `;
    },

    gradeTable: (students, components) => {
      const headers = [
        `<div class="grade-header" style="width:100px">STT</div>`,
        `<div class="grade-header" style="width:300px; text-align:left; padding-left:20px">Họ và tên</div>`,
        ...components.map(c => `<div class="grade-header" style="width:120px" title="${c.component_name}">${c.component_name}</div>`),
        `<div class="grade-header" style="width:100px">Tổng</div>`,
        `<div class="grade-header" style="flex:1; min-width:100px">Trạng thái</div>`
      ].join('');

      return `
        <div class="grade-table-header">
          <div class="grade-search-box">
            <input type="search"
                   class="grade-search-input"
                   id="searchInput"
                   placeholder="Tìm kiếm sinh viên..."
                   aria-label="Tìm kiếm sinh viên">
            <button class="grade-search-btn" type="button" id="searchBtn"></button>
          </div>
          <div class="grade-actions">
            <button class="export-grade-btn" type="button" id="exportBtn">Xuất bảng điểm</button>
            <button class="save-grade-btn" type="button" id="saveGradeBtn">Lưu</button>
          </div>
        </div>
        <div class="grade-table-container">
          <div class="grade-table-head">${headers}</div>
          <div class="grade-table-body" id="gradeTableBody">
            ${Templates.gradeTableBody(students, components)}
          </div>
        </div>
      `;
    },

    gradeTableBody: (students, components) => {
      let stt = 1;
      return students.map(s => {
        const enrollmentId = s.enrollment_id;
        const cells = [
          `<div class="grade-cell" style="width:100px">${stt}</div>`,
          `<div class="grade-cell" style="width:300px; text-align:left; justify-content:flex-start; padding-left:20px">${s.full_name}</div>`,
          ...components.map(c => {
            const v = getScore(enrollmentId, c.component_id);
            return `
              <div class="grade-cell" style="width:120px">
                <input type="number"
                       class="grade-input"
                       value="${v ?? ''}"
                       min="0"
                       max="10"
                       step="0.1"
                       data-enrollment-id="${enrollmentId}"
                       data-component-id="${c.component_id}">
              </div>
            `;
          }),
        ];

        const rawTotal = computeTotalRaw(enrollmentId);
        const total = roundScore1(rawTotal);
        const status = computeStatus(total);
        const totalClass = status ? ('grade-total--' + status) : 'grade-total--empty';
        cells.push(`<div class="grade-cell grade-total ${totalClass}" style="width:100px" data-total-for="${enrollmentId}" title="${getStatusTooltip(status, rawTotal, total)}">${total === null ? '_' : format1(total)}</div>`);
        cells.push(`<div class="grade-cell" style="flex:1; min-width:100px; border-right:none" data-status-for="${enrollmentId}">${Templates.getStatusBadge(status)}</div>`);

        stt += 1;
        return `<div class="grade-table-row" role="row" data-enrollment-id="${enrollmentId}">${cells.join('')}</div>`;
      }).join('');
    }
  };

  const Renderer = {
    renderStructure: () => {
      if (!structureTable) return;
      structureTable.innerHTML = Templates.structureTable(AppState.components);
      Renderer.attachStructureEvents();
    },

    renderGrades: () => {
      if (!gradeTable) return;
      gradeTable.innerHTML = Templates.gradeTable(AppState.filteredStudents, AppState.components);
      Renderer.attachGradeEvents();
    },

    renderGradeBody: () => {
      const body = document.getElementById('gradeTableBody');
      if (!body) return;
      body.innerHTML = Templates.gradeTableBody(AppState.filteredStudents, AppState.components);
      Renderer.attachGradeInputEvents();
    },

    attachStructureEvents: () => {
      const saveStructureBtn = document.getElementById('saveStructureBtn');
      if (saveStructureBtn) saveStructureBtn.addEventListener('click', EventHandlers.handleSaveStructure);

      const inputs = document.querySelectorAll('.structure-input');
      inputs.forEach((input) => {
        input.addEventListener('change', EventHandlers.handleStructureInputChange);
        input.addEventListener('input', EventHandlers.handleStructureInputChange);
      });
    },

    attachGradeEvents: () => {
      const searchInput = document.getElementById('searchInput');
      const searchBtn = document.getElementById('searchBtn');
      if (searchInput) searchInput.addEventListener('input', EventHandlers.handleSearch);
      if (searchBtn) searchBtn.addEventListener('click', EventHandlers.handleSearch);

      const saveGradeBtn = document.getElementById('saveGradeBtn');
      if (saveGradeBtn) saveGradeBtn.addEventListener('click', EventHandlers.handleSaveGrades);

      const exportBtn = document.getElementById('exportBtn');
      if (exportBtn) exportBtn.addEventListener('click', EventHandlers.handleExport);

      Renderer.attachGradeInputEvents();
    },

    attachGradeInputEvents: () => {
      const gradeInputs = document.querySelectorAll('.grade-input');
      gradeInputs.forEach((input) => {
        input.addEventListener('change', EventHandlers.handleGradeInputChange);
        input.addEventListener('blur', EventHandlers.handleGradeInputChange);
      });
    }
  };

  const EventHandlers = {
    handleSearch: () => {
      const searchInput = document.getElementById('searchInput');
      const term = (searchInput ? searchInput.value : '').toLowerCase().trim();
      if (!term) {
        AppState.filteredStudents = [...AppState.students];
      } else {
        AppState.filteredStudents = AppState.students.filter((s) => {
          const name = (s.full_name || '').toLowerCase();
          const code = (s.student_code || '').toLowerCase();
          return name.includes(term) || code.includes(term);
        });
      }
      Renderer.renderGradeBody();
    },

    handleStructureInputChange: (event) => {
      const input = event.target;
      const componentId = parseInt(input.dataset.componentId, 10);
      if (!componentId) return;
      const raw = (input.value ?? '').toString().trim();
      const n = raw === '' ? 0 : parseInt(raw, 10);
      const w = Number.isFinite(n) ? Math.max(0, Math.min(100, n)) : 0;
      const idx = AppState.components.findIndex(c => c.component_id === componentId);
      if (idx !== -1) {
        AppState.components[idx] = { ...AppState.components[idx], weight_percent: w };
        AppState.structureDirty = true;
      }
    },

    handleSaveStructure: async () => {
      try {
        if (AppState.savingStructure) return;
        const saveBtn = document.getElementById('saveStructureBtn');
        AppState.savingStructure = true;
        setButtonLoading(saveBtn, true, 'Đang lưu...');

        // Build payload matching existing controller contract: [{id, component, weight}]
        const structurePayload = AppState.components.map((c) => ({
          id: c.component_id,
          component: c.component_name,
          weight: Math.round(getWeightPercent(c))
        }));

        const sum = structurePayload.reduce((acc, it) => acc + (parseInt(it.weight, 10) || 0), 0);
        if (sum !== 100) {
          TopWarning.show({
            heading: 'Cấu trúc điểm không hợp lệ',
            detail: `Tổng tỉ trọng hiện tại là ${sum}%. Vui lòng điều chỉnh để tổng = 100%`
          });
          return;
        }

        const result = await ApiService.saveStructureData(structurePayload);
        if (result && result.success) {
          TopWarning.show({ heading: 'Thành công', detail: result.message || 'Lưu cấu trúc điểm thành công' });
          AppState.structureDirty = false;
          // Recompute totals in current view
          Renderer.renderGradeBody();
          return;
        }

        TopWarning.show({ heading: 'Không thể lưu', detail: (result && result.message) ? result.message : 'Không thể lưu cấu trúc điểm' });
      } catch (e) {
        console.error('Lỗi khi lưu cấu trúc điểm:', e);
        TopWarning.show({ heading: 'Lỗi khi lưu', detail: 'Có lỗi xảy ra khi lưu cấu trúc điểm' });
      } finally {
        const saveBtn = document.getElementById('saveStructureBtn');
        AppState.savingStructure = false;
        setButtonLoading(saveBtn, false);
      }
    },

    handleGradeInputChange: (event) => {
      const input = event.target;
      const enrollmentId = parseInt(input.dataset.enrollmentId, 10);
      const componentId = parseInt(input.dataset.componentId, 10);
      if (!enrollmentId || !componentId) return;

      const value = parseScoreValue(input.value);
      setScore(enrollmentId, componentId, value);

      // Update total + status for this row
      const rawTotal = computeTotalRaw(enrollmentId);
      const total = roundScore1(rawTotal);
      const status = computeStatus(total);

      const row = input.closest('.grade-table-row');
      if (!row) return;
      const totalCell = row.querySelector(`[data-total-for="${enrollmentId}"]`);
      const statusCell = row.querySelector(`[data-status-for="${enrollmentId}"]`);
      if (totalCell) {
        totalCell.textContent = (total === null ? '_' : format1(total));
        applyTotalCellDecorations(totalCell, rawTotal, total, status);
      }
      if (statusCell) statusCell.innerHTML = Templates.getStatusBadge(status);
    },

    handleSaveGrades: async () => {
      try {
        if (AppState.savingGrades) return;
        const saveBtn = document.getElementById('saveGradeBtn');
        AppState.savingGrades = true;
        setButtonLoading(saveBtn, true, 'Đang lưu...');

        // Collect from DOM so it always reflects what user sees.
        const inputs = Array.from(document.querySelectorAll('.grade-input'));
        const scores = [];
        const seen = new Set();

        for (const input of inputs) {
          const enrollmentId = parseInt(input.dataset.enrollmentId, 10);
          const componentId = parseInt(input.dataset.componentId, 10);
          if (!enrollmentId || !componentId) continue;

          const score = parseScoreValue(input.value);
          if (score === null) continue;

          const key = `${enrollmentId}:${componentId}`;
          if (seen.has(key)) continue;
          seen.add(key);
          scores.push({ enrollment_id: enrollmentId, component_id: componentId, score });
        }

        if (scores.length === 0) {
          TopWarning.show({ heading: 'Không thể lưu', detail: 'Vui lòng nhập điểm hợp lệ trước khi lưu' });
          return;
        }

        const result = await ApiService.saveGradeData(scores);
        if (result && result.success) {
          TopWarning.show({ heading: 'Thành công', detail: result.message || 'Lưu điểm thành công.' });

          // Refresh from server so UI reflects actual DB
          const fresh = await ApiService.getGradingData(true);
          if (fresh && !fresh.message) {
            initializeFromApiPayload(fresh);
          }
          return;
        }

        TopWarning.show({ heading: 'Không thể lưu', detail: (result && result.message) ? result.message : 'Không thể lưu điểm.' });
      } catch (e) {
        console.error('Lỗi khi lưu bảng điểm:', e);
        TopWarning.show({ heading: 'Lỗi khi lưu', detail: 'Có lỗi xảy ra khi lưu bảng điểm' });
      } finally {
        const saveBtn = document.getElementById('saveGradeBtn');
        AppState.savingGrades = false;
        setButtonLoading(saveBtn, false);
      }
    },

    handleExport: async () => {
      try {
        const result = await ApiService.exportGradeData();
        if (result.success) {
          const url = window.URL.createObjectURL(result.blob);
          const a = document.createElement('a');
          a.href = url;
          a.download = result.filename;
          document.body.appendChild(a);
          a.click();
          document.body.removeChild(a);
          window.URL.revokeObjectURL(url);
        }
      } catch (e) {
        console.error('Lỗi khi xuất bảng điểm:', e);
        alert('Có lỗi xảy ra khi xuất bảng điểm');
      }
    }
  };

  const initializeFromApiPayload = (payload) => {
    const structure = Array.isArray(payload.structure) ? payload.structure : [];
    const students = Array.isArray(payload.students) ? payload.students : [];
    const scores = Array.isArray(payload.scores) ? payload.scores : [];

    // Components: strictly component_id based
    AppState.components = structure
      .map((c, idx) => ({
        component_id: Number(c.component_id),
        grading_scheme_id: c.grading_scheme_id != null ? Number(c.grading_scheme_id) : undefined,
        component_name: (c.component_name ?? c.name ?? '').toString(),
        order_no: c.order_no != null ? Number(c.order_no) : idx,
        weight_percent: c.weight_percent != null ? Number(c.weight_percent) : (c.weight != null ? Number(c.weight) * 100 : 0)
      }))
      .filter(c => Number.isFinite(c.component_id) && c.component_id > 0)
      .sort((a, b) => (a.order_no ?? 0) - (b.order_no ?? 0));

    // Students
    AppState.students = students.map((s) => ({
      enrollment_id: Number(s.enrollment_id),
      full_name: (s.full_name ?? '').toString(),
      student_code: (s.student_code ?? '').toString()
    })).filter(s => Number.isFinite(s.enrollment_id) && s.enrollment_id > 0);
    AppState.filteredStudents = [...AppState.students];

    // Scores map
    AppState.scores = new Map();
    scores.forEach((r) => {
      const enrollmentId = Number(r.enrollment_id);
      const componentId = Number(r.component_id);
      if (!Number.isFinite(enrollmentId) || !Number.isFinite(componentId) || enrollmentId <= 0 || componentId <= 0) return;
      const v = (r.score_value ?? r.score);
      const score = (v === null || v === undefined) ? null : Number(v);
      setScore(enrollmentId, componentId, (Number.isFinite(score) ? score : null));
    });

    Renderer.renderStructure();
    Renderer.renderGrades();
  };

  const initializeApp = async () => {
    try {
      if (!ApiService || typeof ApiService.getGradingData !== 'function') {
        alert('Thiếu ApiService (dataGrading.js).');
        return;
      }

      const result = await ApiService.getGradingData();
      if (result && !result.message) {
        initializeFromApiPayload(result);
        return;
      }

      alert((result && result.message) ? result.message : 'Có lỗi xảy ra khi tải dữ liệu');
    } catch (e) {
      console.error('Lỗi khi khởi tạo ứng dụng:', e);
      alert('Có lỗi xảy ra khi tải dữ liệu');
    }
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeApp);
  } else {
    initializeApp();
  }

  if (typeof module !== 'undefined' && module.exports) {
    module.exports = { AppState, Templates, Renderer, EventHandlers };
  }
})();