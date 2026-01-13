// Mock data cho cấu trúc điểm
const mockStructureData = [
  { id: 1, component: "Chuyên cần", weight: 10 },
  { id: 2, component: "Giữa kì", weight: 40 },
  { id: 3, component: "Cuối kì", weight: 50 },
  { id: 4, component: "Tổng", weight: 100 }
];

// Mock data cho bảng điểm (hơn 20 bản ghi)
const mockGradeData = [
  { id: 1, stt: 1, name: "Nguyễn Văn A", attendance: 10, midterm: 8.5, final: 9.0, total: 8.9, status: "passed" },
  { id: 2, stt: 2, name: "Trần Thị B", attendance: 9, midterm: 7.0, final: 8.0, total: 7.7, status: "passed" },
  { id: 3, stt: 3, name: "Lê Văn C", attendance: 5, midterm: 4.0, final: 5.5, total: 5.0, status: "warning" },
  { id: 4, stt: 4, name: "Phạm Thị D", attendance: 0, midterm: 3.0, final: 4.0, total: 3.4, status: "failed" },
  { id: 5, stt: 5, name: "Hoàng Văn E", attendance: 10, midterm: 9.0, final: 9.5, total: 9.4, status: "passed" },
  { id: 6, stt: 6, name: "Đặng Thị F", attendance: 8, midterm: 6.5, final: 7.5, total: 7.1, status: "passed" },
  { id: 7, stt: 7, name: "Bùi Văn G", attendance: 6, midterm: 5.0, final: 6.0, total: 5.6, status: "warning" },
  { id: 8, stt: 8, name: "Vũ Thị H", attendance: 10, midterm: 8.0, final: 8.5, total: 8.3, status: "passed" },
  { id: 9, stt: 9, name: "Đỗ Văn I", attendance: 7, midterm: 6.0, final: 7.0, total: 6.6, status: "warning" },
  { id: 10, stt: 10, name: "Ngô Thị K", attendance: 10, midterm: 9.5, final: 9.0, total: 9.2, status: "passed" },
  { id: 11, stt: 11, name: "Hồ Văn L", attendance: 4, midterm: 3.5, final: 4.5, total: 4.1, status: "failed" },
  { id: 12, stt: 12, name: "Lý Thị M", attendance: 9, midterm: 8.0, final: 8.0, total: 8.0, status: "passed" },
  { id: 13, stt: 13, name: "Phan Văn N", attendance: 10, midterm: 7.5, final: 8.5, total: 8.2, status: "passed" },
  { id: 14, stt: 14, name: "Vương Thị O", attendance: 8, midterm: 7.0, final: 7.5, total: 7.3, status: "passed" },
  { id: 15, stt: 15, name: "Trịnh Văn P", attendance: 5, midterm: 4.5, final: 5.0, total: 4.9, status: "warning" },
  { id: 16, stt: 16, name: "Chu Thị Q", attendance: 10, midterm: 9.0, final: 9.0, total: 9.0, status: "passed" },
  { id: 17, stt: 17, name: "Tô Văn R", attendance: 3, midterm: 2.5, final: 3.0, total: 2.8, status: "failed" },
  { id: 18, stt: 18, name: "Lâm Thị S", attendance: 9, midterm: 8.5, final: 8.0, total: 8.2, status: "passed" },
  { id: 19, stt: 19, name: "Kim Văn T", attendance: 7, midterm: 6.5, final: 7.0, total: 6.8, status: "warning" },
  { id: 20, stt: 20, name: "Mai Thị U", attendance: 10, midterm: 8.0, final: 9.0, total: 8.6, status: "passed" },
  { id: 21, stt: 21, name: "Cao Văn V", attendance: 6, midterm: 5.5, final: 6.5, total: 6.1, status: "warning" },
  { id: 22, stt: 22, name: "Đinh Thị X", attendance: 8, midterm: 7.5, final: 8.0, total: 7.8, status: "passed" },
  { id: 23, stt: 23, name: "Trương Văn Y", attendance: 9, midterm: 8.0, final: 8.5, total: 8.3, status: "passed" },
  { id: 24, stt: 24, name: "Lưu Thị Z", attendance: 10, midterm: 9.5, final: 9.5, total: 9.5, status: "passed" },
  { id: 25, stt: 25, name: "Dương Văn AA", attendance: 4, midterm: 3.0, final: 4.0, total: 3.6, status: "failed" }
];

// API service mô phỏng (sau này sẽ thay bằng API thật)
const ApiService = {
  // Lấy dữ liệu cấu trúc điểm
  getStructureData: async () => {
    // Giả lập API call
    return new Promise(resolve => {
      setTimeout(() => {
        resolve({
          success: true,
          data: mockStructureData
        });
      }, 300);
    });
  },

  // Lấy dữ liệu bảng điểm
  getGradeData: async () => {
    // Giả lập API call
    return new Promise(resolve => {
      setTimeout(() => {
        resolve({
          success: true,
          data: mockGradeData
        });
      }, 500);
    });
  },

  // Lưu cấu trúc điểm
  saveStructureData: async (data) => {
    // Giả lập API call
    return new Promise(resolve => {
      setTimeout(() => {
        console.log('Đã lưu cấu trúc điểm:', data);
        // Cập nhật mock data
        mockStructureData.forEach((item, index) => {
          if (data[index]) {
            item.weight = data[index].weight;
          }
        });
        resolve({
          success: true,
          message: 'Cập nhật cấu trúc điểm thành công'
        });
      }, 500);
    });
  },

  // Lưu bảng điểm
  saveGradeData: async (data) => {
    // Giả lập API call
    return new Promise(resolve => {
      setTimeout(() => {
        console.log('Đã lưu bảng điểm:', data);
        // Cập nhật mock data
        data.forEach(updatedStudent => {
          const index = mockGradeData.findIndex(s => s.id === updatedStudent.id);
          if (index !== -1) {
            mockGradeData[index] = { ...mockGradeData[index], ...updatedStudent };
          }
        });
        resolve({
          success: true,
          message: 'Cập nhật điểm thành công'
        });
      }, 500);
    });
  },

  // Xuất bảng điểm
  exportGradeData: async () => {
    // Giả lập API call
    return new Promise(resolve => {
      setTimeout(() => {
        // Tạo dữ liệu cho export
        const exportData = {
          structure: mockStructureData,
          grades: mockGradeData,
          exportDate: new Date().toISOString()
        };
        
        const blob = new Blob([JSON.stringify(exportData, null, 2)], 
          { type: 'application/json' });
        resolve({
          success: true,
          blob: blob,
          filename: `bang-diem-${new Date().toISOString().split('T')[0]}.json`
        });
      }, 500);
    });
  }
};

// Export các biến và hàm cần thiết
if (typeof module !== 'undefined' && module.exports) {
  module.exports = { mockStructureData, mockGradeData, ApiService };
}