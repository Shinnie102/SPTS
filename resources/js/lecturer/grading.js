// DOM Elements
const structureTable = document.getElementById('structureTable');
const gradeTable = document.getElementById('gradeTable');

// Templates
const Templates = {
  // Template cho bảng cấu trúc điểm - SỬA: Thêm input cho tỉ trọng
  structureTable: (data) => {
    return `
      <div class="structure-head" role="rowgroup">
        <div class="structure-col" role="columnheader">Thành phần điểm</div>
        <div class="structure-col" role="columnheader">Tỉ trọng (%)</div>
      </div>
      <div class="structure-body" role="rowgroup">
        ${data.map(item => `
          <div class="structure-row" role="row" data-id="${item.id}">
            <div class="structure-cell" role="cell">${item.component}</div>
            <div class="structure-cell" role="cell">
              <input type="number" 
                     class="structure-input" 
                     value="${item.weight}" 
                     min="0" 
                     max="100" 
                     step="1"
                     data-id="${item.id}"
                     ${item.component === 'Tổng' ? 'disabled' : ''}>
            </div>
          </div>
        `).join('')}
      </div>
      <div class="structure-footer">
        <button class="save-structure-btn" type="button" id="saveStructureBtn">
          Lưu
        </button>
      </div>
    `;
  },

  // Template cho bảng điểm (bao gồm header và body)
  gradeTable: (data) => {
    return `
      <div class="grade-table-header">
        <div class="grade-search-box">
          <input type="search" 
                 class="grade-search-input" 
                 id="searchInput"
                 placeholder="Tìm kiếm sinh viên..."
                 aria-label="Tìm kiếm sinh viên">
          <button class="grade-search-btn" type="button" id="searchBtn">
          </button>
        </div>
        <div class="grade-actions">
          <button class="export-grade-btn" type="button" id="exportBtn">
            Xuất bảng điểm
          </button>
          <button class="save-grade-btn" type="button" id="saveGradeBtn">
            Lưu
          </button>
        </div>
      </div>
      <div class="grade-table-container">
        <div class="grade-table-head">
          <div class="grade-header">STT</div>
          <div class="grade-header">Họ và tên</div>
          <div class="grade-header">Chuyên cần</div>
          <div class="grade-header">Giữa kì</div>
          <div class="grade-header">Cuối kì</div>
          <div class="grade-header">Tổng</div>
          <div class="grade-header">Trạng thái</div>
        </div>
        <div class="grade-table-body" id="gradeTableBody">
          ${Templates.gradeTableBody(data)}
        </div>
      </div>
    `;
  },

  // Template riêng cho body của bảng điểm
  gradeTableBody: (data) => {
    return data.map(item => `
      <div class="grade-table-row" role="row" data-id="${item.id}">
        <div class="grade-cell">${item.stt}</div>
        <div class="grade-cell">${item.name}</div>
        <div class="grade-cell">
          <input type="number" 
                 class="grade-input" 
                 value="${item.attendance || ''}" 
                 min="0" 
                 max="10" 
                 step="0.1"
                 data-field="attendance">
        </div>
        <div class="grade-cell">
          <input type="number" 
                 class="grade-input" 
                 value="${item.midterm || ''}" 
                 min="0" 
                 max="10" 
                 step="0.1"
                 data-field="midterm">
        </div>
        <div class="grade-cell">
          <input type="number" 
                 class="grade-input" 
                 value="${item.final || ''}" 
                 min="0" 
                 max="10" 
                 step="0.1"
                 data-field="final">
        </div>
        <div class="grade-cell">${item.total || '_'}</div>
        <div class="grade-cell">
          ${Templates.getStatusBadge(item.status)}
        </div>
      </div>
    `).join('');
  },

  // Template cho badge trạng thái
  getStatusBadge: (status) => {
    const statusMap = {
      'passed': { text: 'Đạt', class: 'status-passed' },
      'failed': { text: 'Không đạt', class: 'status-failed' },
      'warning': { text: 'Nguy cơ', class: 'status-warning' },
      '': { text: 'Chưa có', class: 'status-empty' }
    };
    
    const statusInfo = statusMap[status] || statusMap[''];
    return `<span class="status-badge ${statusInfo.class}">${statusInfo.text}</span>`;
  }
};

// State management
const AppState = {
  structureData: [],
  gradeData: [],
  filteredGradeData: []
};

// Render functions
const Renderer = {
  // Render bảng cấu trúc điểm
  renderStructureTable: () => {
    if (!structureTable) return;
    
    structureTable.innerHTML = Templates.structureTable(AppState.structureData);
    Renderer.attachStructureEvents();
  },

  // Render bảng điểm
  renderGradeTable: () => {
    if (!gradeTable) return;
    
    gradeTable.innerHTML = Templates.gradeTable(AppState.filteredGradeData);
    Renderer.attachGradeEvents();
  },

  // Render chỉ body của bảng điểm (tối ưu khi tìm kiếm)
  renderGradeTableBody: () => {
    const gradeTableBody = document.getElementById('gradeTableBody');
    if (gradeTableBody) {
      gradeTableBody.innerHTML = Templates.gradeTableBody(AppState.filteredGradeData);
      Renderer.attachGradeInputEvents();
    }
  }
};

// Event handlers
const EventHandlers = {
  // Tìm kiếm sinh viên
  handleSearch: () => {
    const searchInput = document.getElementById('searchInput');
    if (!searchInput) return;

    const searchTerm = searchInput.value.toLowerCase().trim();
    
    if (searchTerm === '') {
      AppState.filteredGradeData = [...AppState.gradeData];
    } else {
      AppState.filteredGradeData = AppState.gradeData.filter(student => 
        student.name.toLowerCase().includes(searchTerm) ||
        student.stt.toString().includes(searchTerm)
      );
    }
    
    Renderer.renderGradeTableBody();
  },

  // Lưu cấu trúc điểm
  handleSaveStructure: async () => {
    try {
      // Thu thập dữ liệu từ các input tỉ trọng
      const updatedData = AppState.structureData.map(item => {
        const input = document.querySelector(`.structure-input[data-id="${item.id}"]`);
        if (input) {
          return {
            ...item,
            weight: parseInt(input.value) || 0
          };
        }
        return item;
      });

      // Kiểm tra tổng tỉ trọng = 100%
      const totalWeight = updatedData
        .filter(item => item.component !== 'Tổng')
        .reduce((sum, item) => sum + item.weight, 0);
      
      if (totalWeight !== 100) {
        alert(`Tổng tỉ trọng hiện tại là ${totalWeight}%. Vui lòng điều chỉnh để tổng = 100%`);
        return;
      }

      // Cập nhật dòng "Tổng" để luôn = 100
      const totalItemIndex = updatedData.findIndex(item => item.component === 'Tổng');
      if (totalItemIndex !== -1) {
        updatedData[totalItemIndex].weight = 100;
      }

      const result = await ApiService.saveStructureData(updatedData);
      if (result.success) {
        alert(result.message || 'Cập nhật cấu trúc điểm thành công!');
        // Cập nhật state và re-render
        AppState.structureData = updatedData;
        Renderer.renderStructureTable();
        
        // Tính toán lại điểm tổng cho tất cả sinh viên
        EventHandlers.recalculateAllGrades();
      }
    } catch (error) {
      console.error('Lỗi khi lưu cấu trúc điểm:', error);
      alert('Có lỗi xảy ra khi lưu cấu trúc điểm');
    }
  },

  // Tính toán lại điểm tổng cho tất cả sinh viên
  recalculateAllGrades: () => {
    AppState.gradeData.forEach(student => {
      if (student.attendance !== null && student.midterm !== null && student.final !== null) {
        const weights = EventHandlers.getCurrentWeights();
        const total = (student.attendance * weights.attendance) + 
                      (student.midterm * weights.midterm) + 
                      (student.final * weights.final);
        student.total = total.toFixed(1);
        
        // Cập nhật trạng thái
        if (total >= 5.0) {
          student.status = 'passed';
        } else if (total >= 4.0) {
          student.status = 'warning';
        } else {
          student.status = 'failed';
        }
      }
    });
    
    // Cập nhật filtered data và re-render
    AppState.filteredGradeData = [...AppState.gradeData];
    Renderer.renderGradeTableBody();
  },

  // Lấy tỉ trọng hiện tại từ state
  getCurrentWeights: () => {
    const weights = { attendance: 0.1, midterm: 0.4, final: 0.5 }; // Mặc định
    
    AppState.structureData.forEach(item => {
      if (item.component === 'Chuyên cần') weights.attendance = item.weight / 100;
      if (item.component === 'Giữa kì') weights.midterm = item.weight / 100;
      if (item.component === 'Cuối kì') weights.final = item.weight / 100;
    });
    
    return weights;
  },

  // Lưu bảng điểm
  handleSaveGrade: async () => {
    try {
      // Thu thập dữ liệu từ các input
      const updatedData = AppState.gradeData.map(student => {
        const row = document.querySelector(`.grade-table-row[data-id="${student.id}"]`);
        if (row) {
          return {
            ...student,
            attendance: parseFloat(row.querySelector('[data-field="attendance"]').value) || null,
            midterm: parseFloat(row.querySelector('[data-field="midterm"]').value) || null,
            final: parseFloat(row.querySelector('[data-field="final"]').value) || null
          };
        }
        return student;
      });

      const result = await ApiService.saveGradeData(updatedData);
      if (result.success) {
        alert(result.message || 'Cập nhật điểm thành công!');
        // Cập nhật state và re-render
        AppState.gradeData = updatedData;
        AppState.filteredGradeData = [...updatedData];
        Renderer.renderGradeTableBody();
      }
    } catch (error) {
      console.error('Lỗi khi lưu bảng điểm:', error);
      alert('Có lỗi xảy ra khi lưu bảng điểm');
    }
  },

  // Xuất bảng điểm
  handleExport: async () => {
    try {
      const result = await ApiService.exportGradeData();
      if (result.success) {
        // Tạo link download
        const url = window.URL.createObjectURL(result.blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = result.filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
      }
    } catch (error) {
      console.error('Lỗi khi xuất bảng điểm:', error);
      alert('Có lỗi xảy ra khi xuất bảng điểm');
    }
  },

  // Tính toán điểm tổng khi input thay đổi
  handleGradeInputChange: (event) => {
    const input = event.target;
    const row = input.closest('.grade-table-row');
    const id = parseInt(row.dataset.id);
    
    // Tìm student trong state
    const studentIndex = AppState.gradeData.findIndex(s => s.id === id);
    if (studentIndex === -1) return;
    
    const student = AppState.gradeData[studentIndex];
    
    // Cập nhật giá trị
    const field = input.dataset.field;
    const value = parseFloat(input.value) || null;
    student[field] = value;
    
    // Tính lại tổng điểm dựa trên cấu trúc điểm hiện tại
    if (student.attendance !== null && student.midterm !== null && student.final !== null) {
      const weights = EventHandlers.getCurrentWeights();
      
      // Tính tổng điểm theo tỉ trọng
      const total = (student.attendance * weights.attendance) + 
                    (student.midterm * weights.midterm) + 
                    (student.final * weights.final);
      student.total = total.toFixed(1);
      
      // Cập nhật trạng thái
      if (total >= 5.0) {
        student.status = 'passed';
      } else if (total >= 4.0) {
        student.status = 'warning';
      } else {
        student.status = 'failed';
      }
      
      // Cập nhật hiển thị
      const totalCell = row.querySelector('.grade-cell:nth-child(6)');
      const statusCell = row.querySelector('.grade-cell:nth-child(7)');
      
      if (totalCell) totalCell.textContent = student.total;
      if (statusCell) statusCell.innerHTML = Templates.getStatusBadge(student.status);
    } else {
      // Nếu thiếu điểm, reset tổng và trạng thái
      student.total = null;
      student.status = '';
      
      const totalCell = row.querySelector('.grade-cell:nth-child(6)');
      const statusCell = row.querySelector('.grade-cell:nth-child(7)');
      
      if (totalCell) totalCell.textContent = '_';
      if (statusCell) statusCell.innerHTML = Templates.getStatusBadge('');
    }
  },

  // Xử lý thay đổi tỉ trọng
  handleStructureInputChange: (event) => {
    const input = event.target;
    const id = parseInt(input.dataset.id);
    
    // Tìm và cập nhật trong state
    const itemIndex = AppState.structureData.findIndex(item => item.id === id);
    if (itemIndex !== -1) {
      AppState.structureData[itemIndex].weight = parseInt(input.value) || 0;
    }
  }
};

// Attach events
Renderer.attachStructureEvents = () => {
  const saveStructureBtn = document.getElementById('saveStructureBtn');
  if (saveStructureBtn) {
    saveStructureBtn.addEventListener('click', EventHandlers.handleSaveStructure);
  }

  // Thêm event cho các input tỉ trọng
  const structureInputs = document.querySelectorAll('.structure-input');
  structureInputs.forEach(input => {
    input.addEventListener('change', EventHandlers.handleStructureInputChange);
    input.addEventListener('input', EventHandlers.handleStructureInputChange);
  });
};

Renderer.attachGradeEvents = () => {
  // Search
  const searchInput = document.getElementById('searchInput');
  const searchBtn = document.getElementById('searchBtn');
  
  if (searchInput) {
    searchInput.addEventListener('input', EventHandlers.handleSearch);
  }
  
  if (searchBtn) {
    searchBtn.addEventListener('click', EventHandlers.handleSearch);
  }

  // Save
  const saveGradeBtn = document.getElementById('saveGradeBtn');
  if (saveGradeBtn) {
    saveGradeBtn.addEventListener('click', EventHandlers.handleSaveGrade);
  }

  // Export
  const exportBtn = document.getElementById('exportBtn');
  if (exportBtn) {
    exportBtn.addEventListener('click', EventHandlers.handleExport);
  }

  // Attach input events
  Renderer.attachGradeInputEvents();
};

Renderer.attachGradeInputEvents = () => {
  const gradeInputs = document.querySelectorAll('.grade-input');
  gradeInputs.forEach(input => {
    input.addEventListener('change', EventHandlers.handleGradeInputChange);
    input.addEventListener('blur', EventHandlers.handleGradeInputChange);
  });
};

// Initialize app
const initializeApp = async () => {
  try {
    // Load dữ liệu cấu trúc điểm
    const structureResult = await ApiService.getStructureData();
    if (structureResult.success) {
      AppState.structureData = structureResult.data;
      Renderer.renderStructureTable();
    }

    // Load dữ liệu bảng điểm
    const gradeResult = await ApiService.getGradeData();
    if (gradeResult.success) {
      AppState.gradeData = gradeResult.data;
      AppState.filteredGradeData = [...gradeResult.data];
      Renderer.renderGradeTable();
    }
  } catch (error) {
    console.error('Lỗi khi khởi tạo ứng dụng:', error);
    alert('Có lỗi xảy ra khi tải dữ liệu');
  }
};

// Khởi chạy ứng dụng khi DOM đã sẵn sàng
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initializeApp);
} else {
  initializeApp();
}

// Export cho testing (nếu cần)
if (typeof module !== 'undefined' && module.exports) {
  module.exports = { Templates, AppState, Renderer, EventHandlers };
}