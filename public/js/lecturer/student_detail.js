// student_detail.js
// Report page only: Bootstrap-style modal + fetch student detail JSON

(() => {
  const REPORT_PATH_RE = /^\/lecturer\/class\/(\d+)\/report\/?$/;

  const getClassIdFromUrl = () => {
    const path = window.location.pathname || '';
    const match = path.match(REPORT_PATH_RE);
    return match ? parseInt(match[1], 10) : null;
  };

  const fetchJson = async (url) => {
    const response = await fetch(url, {
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json'
      }
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

  const setText = (id, value) => {
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent = value == null || value === '' ? '—' : String(value);
  };

  const round1 = (num) => {
    const n = Number(num);
    if (!Number.isFinite(n)) return null;
    return Math.round((n + Number.EPSILON) * 10) / 10;
  };

  const showTab = (tabKey) => {
    const modal = document.getElementById('studentDetailModal');
    if (!modal) return;

    const tabs = Array.from(modal.querySelectorAll('.sd-tab'));
    const panels = Array.from(modal.querySelectorAll('.sd-panel'));

    tabs.forEach((t) => t.classList.toggle('active', t.dataset.tab === tabKey));
    panels.forEach((p) => p.classList.toggle('active', p.dataset.panel === tabKey));
  };

  const setStatusPill = (code, label) => {
    const pill = document.getElementById('sdFinalStatus');
    if (!pill) return;

    pill.classList.remove('passed', 'warning', 'failed');
    const normalized = (code || '').toLowerCase();
    if (['passed', 'pass'].includes(normalized)) pill.classList.add('passed');
    else if (['warning', 'warn'].includes(normalized)) pill.classList.add('warning');
    else if (['failed', 'fail'].includes(normalized)) pill.classList.add('failed');

    pill.textContent = label || '—';
  };

  const renderWarnings = (warnings) => {
    const root = document.getElementById('sdWarnings');
    if (!root) return;

    const items = Array.isArray(warnings) ? warnings : [];
    if (items.length === 0) {
      root.innerHTML = '<div style="color: rgba(0,0,0,0.6); font-size: 14px;">Không có cảnh báo.</div>';
      return;
    }

    root.innerHTML = items.map((w) => {
      const level = (w.level || '').toLowerCase();
      const color = level === 'danger' ? '#dc3545' : (level === 'warning' ? '#ffc107' : '#28a745');
      const title = (w.title || w.code || 'Cảnh báo');
      const message = (w.message || '');
      return `
        <div style="border: 1px solid rgba(0,0,0,0.08); border-left: 6px solid ${color}; padding: 10px 12px; border-radius: 10px; margin-bottom: 10px; background: #fff;">
          <div style="font-weight: 800; margin-bottom: 4px;">${title}</div>
          <div style="color: rgba(0,0,0,0.7); font-size: 14px;">${message}</div>
        </div>
      `;
    }).join('');
  };

  const renderScores = (components, finalRounded) => {
    const tbody = document.getElementById('sdScoresBody');
    const bars = document.getElementById('sdMiniBars');
    if (!tbody || !bars) return;

    const rows = Array.isArray(components) ? components : [];

    tbody.innerHTML = '';
    bars.innerHTML = '';

    if (rows.length === 0) {
      tbody.innerHTML = '<tr><td colspan="3" style="text-align:center; padding: 14px;">Chưa có dữ liệu điểm.</td></tr>';
      return;
    }

    rows.forEach((c) => {
      const weight = Number.isFinite(Number(c.weight_percent)) ? `${Number(c.weight_percent).toFixed(0)}%` : '—';
      const score = (c.score_rounded == null) ? '—' : Number(c.score_rounded).toFixed(1);

      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${c.component_name || ''}</td>
        <td>${weight}</td>
        <td>${score}</td>
      `;
      tbody.appendChild(tr);

      const barVal = c.score_rounded == null ? null : Number(c.score_rounded);
      const pct = barVal == null || !Number.isFinite(barVal) ? 0 : Math.max(0, Math.min(100, (barVal / 10) * 100));
      const row = document.createElement('div');
      row.className = 'sd-bar-row';
      row.innerHTML = `
        <div style="font-weight: 700; font-size: 13px;">${c.component_name || ''}</div>
        <div class="sd-bar-track"><div class="sd-bar" style="width:${pct}%;"></div></div>
        <div style="font-weight: 800; font-size: 13px;">${score}</div>
      `;
      bars.appendChild(row);
    });

    // Add a final score note
    if (finalRounded != null && Number.isFinite(Number(finalRounded))) {
      const note = document.createElement('div');
      note.style.marginTop = '10px';
      note.style.color = 'rgba(0,0,0,0.65)';
      note.style.fontSize = '13px';
      note.innerHTML = `Điểm tổng kết (làm tròn): <strong>${Number(finalRounded).toFixed(1)}</strong>`;
      bars.appendChild(note);
    }
  };

  const setLoadingState = (isLoading) => {
    const loading = document.getElementById('sdLoading');
    const content = document.getElementById('sdContent');
    const error = document.getElementById('sdError');

    if (loading) loading.hidden = !isLoading;
    if (content) content.hidden = isLoading;
    if (error) error.hidden = true;
  };

  const setErrorState = (message) => {
    const loading = document.getElementById('sdLoading');
    const content = document.getElementById('sdContent');
    const error = document.getElementById('sdError');

    if (loading) loading.hidden = true;
    if (content) content.hidden = true;
    if (error) {
      error.hidden = false;
      error.textContent = message || 'Có lỗi xảy ra.';
    }
  };

  const openModal = () => {
    const modalEl = document.getElementById('studentDetailModal');
    if (!modalEl) return null;

    if (window.bootstrap && window.bootstrap.Modal) {
      const existing = window.bootstrap.Modal.getInstance(modalEl);
      const modal = existing || new window.bootstrap.Modal(modalEl, { backdrop: true, keyboard: true });
      modal.show();
      return modal;
    }

    // Fallback: class-only show
    modalEl.classList.add('show');
    return null;
  };

  const printModal = () => {
    const modalEl = document.getElementById('studentDetailModal');
    const content = document.getElementById('sdContent');
    if (!modalEl || !content || content.hidden) return;

    const title = document.getElementById('studentDetailModalLabel')?.textContent || 'Báo cáo sinh viên';
    const subtitle = document.getElementById('sdStudentLine')?.textContent || '';

    const win = window.open('', '_blank', 'noopener,noreferrer,width=920,height=720');
    if (!win) return;

    const html = `
      <!doctype html>
      <html lang="vi">
        <head>
          <meta charset="utf-8" />
          <meta name="viewport" content="width=device-width, initial-scale=1" />
          <title>${title}</title>
          <style>
            body { font-family: Arial, sans-serif; padding: 18px; }
            h1 { font-size: 18px; margin: 0; }
            .sub { margin: 6px 0 14px; color: #555; font-size: 13px; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 13px; }
            th { background: #f6f6f6; }
          </style>
        </head>
        <body>
          <h1>${title}</h1>
          <div class="sub">${subtitle}</div>
          ${content.innerHTML}
          <script>window.print();</script>
        </body>
      </html>
    `;

    win.document.open();
    win.document.write(html);
    win.document.close();
  };

  const renderDetail = (data) => {
    const student = data.student || {};
    const scores = data.scores || {};
    const attendance = data.attendance || {};

    const code = student.student_code || student.code || '—';
    const name = student.full_name || student.name || '—';

    setText('sdStudentLine', `${code} • ${name}`);
    setText('sdCode', code);
    setText('sdName', name);
    setText('sdEmail', student.email || '—');
    setText('sdMajor', student.major || '—');

    const finalRounded = (scores.final && scores.final.rounded != null) ? Number(scores.final.rounded) : null;
    setText('sdFinalScore', finalRounded == null ? '—' : finalRounded.toFixed(1));

    const statusCode = scores.final?.status?.code || '';
    const statusLabel = scores.final?.status?.label || '';
    setStatusPill(statusCode, statusLabel);

    renderScores(scores.components || [], finalRounded);

    setText('sdMeetings', attendance.total_meetings ?? '—');
    setText('sdPresent', attendance.present ?? 0);
    setText('sdAbsent', attendance.absent ?? 0);
    setText('sdLate', attendance.late ?? 0);
    setText('sdExcused', attendance.excused ?? 0);
    setText('sdUnmarked', attendance.unmarked ?? 0);

    const rate = round1(attendance.attendance_rate_percent);
    setText('sdAttendanceRate', rate == null ? '—' : `${rate.toFixed(1)}%`);

    renderWarnings(data.warnings || []);
  };

  const loadStudentDetail = async (classId, studentId) => {
    setLoadingState(true);
    showTab('info');

    const modal = openModal();
    void modal;

    const result = await fetchJson(`/lecturer/class/${classId}/student/${studentId}/detail`);
    if (result && result.success === false) {
      setErrorState(result.message || 'Không thể tải dữ liệu');
      return;
    }

    setLoadingState(false);

    const content = document.getElementById('sdContent');
    if (content) content.hidden = false;

    renderDetail(result);
  };

  const wireTabs = () => {
    const modal = document.getElementById('studentDetailModal');
    if (!modal) return;

    modal.addEventListener('click', (e) => {
      const btn = e.target.closest('.sd-tab');
      if (!btn) return;
      e.preventDefault();
      showTab(btn.dataset.tab || 'info');
    });
  };

  document.addEventListener('DOMContentLoaded', () => {
    const path = window.location.pathname || '';
    if (!REPORT_PATH_RE.test(path)) return;

    wireTabs();

    const printBtn = document.getElementById('sdPrintBtn');
    if (printBtn) {
      printBtn.addEventListener('click', (e) => {
        e.preventDefault();
        printModal();
      });
    }

    document.addEventListener('lecturer:student-detail', (e) => {
      const classId = e?.detail?.classId ?? getClassIdFromUrl();
      const studentId = e?.detail?.studentId;
      if (!classId || !studentId) return;
      loadStudentDetail(classId, studentId);
    });
  });
})();
