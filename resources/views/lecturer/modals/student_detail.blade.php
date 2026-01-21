<style>
  /* Modal-only styling (scoped) so it won't affect report page UI */
  #studentDetailModal.modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 1055;
    overflow-y: auto;
    padding: 24px 12px;
  }

  #studentDetailModal.modal.show {
    display: block;
  }

  #studentDetailModal .modal-dialog {
    max-width: 920px;
    margin: 0 auto;
  }

  #studentDetailModal .modal-content {
    background: #fff;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
    border: 1px solid #e5e7eb;
  }

  #studentDetailModal .modal-header,
  #studentDetailModal .modal-body,
  #studentDetailModal .modal-footer {
    padding: 14px 16px;
  }

  #studentDetailModal .modal-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    border-bottom: 1px solid #e5e7eb;
  }

  #studentDetailModal .modal-title {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
    color: #000;
  }

  #studentDetailModal .sd-subtitle {
    margin-top: 4px;
    font-size: 13px;
    color: rgba(0, 0, 0, 0.6);
  }

  #studentDetailModal .sd-close {
    background: transparent;
    border: 0;
    font-size: 22px;
    line-height: 1;
    cursor: pointer;
    color: rgba(0, 0, 0, 0.65);
    padding: 0 4px;
  }

  #studentDetailModal .sd-close:hover {
    color: rgba(0, 0, 0, 0.9);
  }

  #studentDetailModal .sd-loading {
    padding: 14px;
    background: #f9f9f9;
    border: 1px solid #e0e0e0;
    border-radius: 10px;
    font-size: 14px;
  }

  #studentDetailModal .sd-error {
    padding: 12px 14px;
    border-radius: 10px;
    border: 1px solid rgba(220, 53, 69, 0.25);
    background: rgba(220, 53, 69, 0.08);
    color: #dc3545;
    font-size: 14px;
  }

  #studentDetailModal .sd-tabs {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 12px;
  }

  #studentDetailModal .sd-tab {
    border: 1px solid #dedcdc;
    background: #fff;
    color: rgba(0, 0, 0, 0.75);
    padding: 8px 12px;
    border-radius: 999px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
  }

  #studentDetailModal .sd-tab.active {
    border-color: #0088f0;
    background: rgba(0, 136, 240, 0.1);
    color: #0088f0;
  }

  #studentDetailModal .sd-panel {
    display: none;
  }

  #studentDetailModal .sd-panel.active {
    display: block;
  }

  #studentDetailModal .sd-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px 14px;
  }

  #studentDetailModal .sd-field {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }

  #studentDetailModal .sd-label {
    font-size: 12px;
    color: rgba(0, 0, 0, 0.6);
  }

  #studentDetailModal .sd-value {
    font-size: 14px;
    color: #000;
    font-weight: 600;
    word-break: break-word;
  }

  #studentDetailModal .sd-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 800;
    line-height: 1.4;
  }

  #studentDetailModal .sd-pill.passed { background: rgba(40, 167, 69, 0.12); color: #28a745; border: 1px solid rgba(40, 167, 69, 0.35); }
  #studentDetailModal .sd-pill.warning { background: rgba(255, 193, 7, 0.14); color: #ffc107; border: 1px solid rgba(255, 193, 7, 0.35); }
  #studentDetailModal .sd-pill.failed { background: rgba(220, 53, 69, 0.12); color: #dc3545; border: 1px solid rgba(220, 53, 69, 0.35); }

  #studentDetailModal .sd-table {
    width: 100%;
    border-collapse: collapse;
  }

  #studentDetailModal .sd-table th,
  #studentDetailModal .sd-table td {
    text-align: left;
    padding: 10px 10px;
    border-bottom: 1px solid #f0f0f0;
    font-size: 14px;
  }

  #studentDetailModal .sd-table thead th {
    border-bottom: 2px solid #e0e0e0;
    font-weight: 700;
  }

  #studentDetailModal .sd-mini-bars {
    display: grid;
    gap: 8px;
    margin-top: 12px;
  }

  #studentDetailModal .sd-bar-row {
    display: grid;
    grid-template-columns: 1.2fr 3fr auto;
    gap: 10px;
    align-items: center;
  }

  #studentDetailModal .sd-bar-track {
    height: 10px;
    background: #f0f0f0;
    border-radius: 999px;
    overflow: hidden;
  }

  #studentDetailModal .sd-bar {
    height: 10px;
    background: #0088f0;
    width: 0;
  }

  #studentDetailModal .modal-footer {
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    flex-wrap: wrap;
  }

  #studentDetailModal .sd-btn {
    border: none;
    padding: 10px 14px;
    border-radius: 10px;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
  }

  #studentDetailModal .sd-btn.secondary {
    border: 1px solid #dedcdc;
    background: #fff;
    color: rgba(0, 0, 0, 0.75);
  }

  #studentDetailModal .sd-btn.primary {
    background: #0088f0;
    color: #fff;
  }

  @media (max-width: 640px) {
    #studentDetailModal .sd-grid { grid-template-columns: 1fr; }
    #studentDetailModal .sd-bar-row { grid-template-columns: 1fr; }
  }

  .modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.45);
    z-index: 1050;
  }

  .modal-backdrop.fade { opacity: 0; }
  .modal-backdrop.show { opacity: 1; }
</style>

<div class="modal fade" id="studentDetailModal" tabindex="-1" aria-labelledby="studentDetailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title" id="studentDetailModalLabel">Chi tiết sinh viên</h5>
          <div class="sd-subtitle" id="sdStudentLine">—</div>
        </div>
        <button type="button" class="sd-close" data-bs-dismiss="modal" aria-label="Đóng">&times;</button>
      </div>

      <div class="modal-body">
        <div id="sdLoading" class="sd-loading">Đang tải dữ liệu…</div>
        <div id="sdError" class="sd-error" hidden></div>

        <div id="sdContent" hidden>
          <div class="sd-tabs" role="tablist">
            <button type="button" class="sd-tab active" data-tab="info">Thông tin</button>
            <button type="button" class="sd-tab" data-tab="scores">Điểm số</button>
            <button type="button" class="sd-tab" data-tab="attendance">Chuyên cần</button>
            <button type="button" class="sd-tab" data-tab="warnings">Cảnh báo</button>
          </div>

          <section class="sd-panel active" data-panel="info">
            <div class="sd-grid">
              <div class="sd-field"><div class="sd-label">MSSV</div><div class="sd-value" id="sdCode">—</div></div>
              <div class="sd-field"><div class="sd-label">Họ tên</div><div class="sd-value" id="sdName">—</div></div>
              <div class="sd-field"><div class="sd-label">Email</div><div class="sd-value" id="sdEmail">—</div></div>
              <div class="sd-field"><div class="sd-label">Ngành</div><div class="sd-value" id="sdMajor">—</div></div>
              <div class="sd-field"><div class="sd-label">Điểm tổng kết</div><div class="sd-value" id="sdFinalScore">—</div></div>
              <div class="sd-field"><div class="sd-label">Trạng thái</div><div class="sd-value"><span id="sdFinalStatus" class="sd-pill">—</span></div></div>
            </div>
          </section>

          <section class="sd-panel" data-panel="scores">
            <table class="sd-table" aria-label="Bảng điểm chi tiết">
              <thead>
                <tr>
                  <th>Thành phần</th>
                  <th style="width: 120px;">Trọng số</th>
                  <th style="width: 110px;">Điểm</th>
                </tr>
              </thead>
              <tbody id="sdScoresBody"></tbody>
            </table>

            <div class="sd-mini-bars" id="sdMiniBars" aria-label="Biểu đồ mini điểm số"></div>
          </section>

          <section class="sd-panel" data-panel="attendance">
            <div class="sd-grid">
              <div class="sd-field"><div class="sd-label">Tổng số buổi</div><div class="sd-value" id="sdMeetings">—</div></div>
              <div class="sd-field"><div class="sd-label">Tỷ lệ chuyên cần</div><div class="sd-value" id="sdAttendanceRate">—</div></div>
              <div class="sd-field"><div class="sd-label">Có mặt</div><div class="sd-value" id="sdPresent">—</div></div>
              <div class="sd-field"><div class="sd-label">Vắng</div><div class="sd-value" id="sdAbsent">—</div></div>
              <div class="sd-field"><div class="sd-label">Muộn</div><div class="sd-value" id="sdLate">—</div></div>
              <div class="sd-field"><div class="sd-label">Có phép</div><div class="sd-value" id="sdExcused">—</div></div>
              <div class="sd-field"><div class="sd-label">Chưa điểm danh</div><div class="sd-value" id="sdUnmarked">—</div></div>
            </div>
          </section>

          <section class="sd-panel" data-panel="warnings">
            <div id="sdWarnings"></div>
          </section>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="sd-btn primary" data-bs-dismiss="modal">Đóng</button>
      </div>
    </div>
  </div>
</div>
