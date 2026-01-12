// data.js
const mockData = {
  stats: {
    totalClasses: 25,
    warnings: 8,
    completedGrading: 12,
    pendingGrading: 7
  },
  
  classes: [
    { code: 'LTM101', name: 'Lập trình mạng', total: 35, status: 'completed', statusText: 'Đã nhập điểm' },
    { code: 'LTM102', name: 'Cấu trúc dữ liệu', total: 42, status: 'pending', statusText: 'Chưa nhập điểm' },
    { code: 'LTM103', name: 'Lập trình web', total: 38, status: 'locked', statusText: 'Đã khóa điểm' },
    { code: 'LTM104', name: 'Cơ sở dữ liệu', total: 45, status: 'completed', statusText: 'Đã nhập điểm' },
    // Thêm 20 lớp mới
    { code: 'LTM201', name: 'Giải tích 1', total: 50, status: 'completed', statusText: 'Đã nhập điểm' },
    { code: 'LTM202', name: 'Giải tích 2', total: 48, status: 'pending', statusText: 'Chưa nhập điểm' },
    { code: 'LTM203', name: 'Xác suất thống kê', total: 40, status: 'locked', statusText: 'Đã khóa điểm' },
    { code: 'LTM204', name: 'Vật lý đại cương', total: 55, status: 'completed', statusText: 'Đã nhập điểm' },
    { code: 'LTM205', name: 'Hóa học đại cương', total: 45, status: 'pending', statusText: 'Chưa nhập điểm' },
    { code: 'LTM206', name: 'Toán rời rạc', total: 38, status: 'completed', statusText: 'Đã nhập điểm' },
    { code: 'LTM207', name: 'Kiến trúc máy tính', total: 42, status: 'locked', statusText: 'Đã khóa điểm' },
    { code: 'LTM208', name: 'Hệ điều hành', total: 47, status: 'completed', statusText: 'Đã nhập điểm' },
    { code: 'LTM209', name: 'Mạng máy tính', total: 52, status: 'pending', statusText: 'Chưa nhập điểm' },
    { code: 'LTM210', name: 'Lập trình C++', total: 60, status: 'completed', statusText: 'Đã nhập điểm' },
    { code: 'LTM211', name: 'Lập trình Java', total: 58, status: 'locked', statusText: 'Đã khóa điểm' },
    { code: 'LTM212', name: 'Lập trình Python', total: 65, status: 'completed', statusText: 'Đã nhập điểm' },
    { code: 'LTM213', name: 'Phân tích thiết kế hệ thống', total: 40, status: 'pending', statusText: 'Chưa nhập điểm' },
    { code: 'LTM214', name: 'Công nghệ phần mềm', total: 45, status: 'completed', statusText: 'Đã nhập điểm' },
    { code: 'LTM215', name: 'Trí tuệ nhân tạo', total: 50, status: 'locked', statusText: 'Đã khóa điểm' },
    { code: 'LTM216', name: 'Học máy', total: 48, status: 'completed', statusText: 'Đã nhập điểm' },
    { code: 'LTM217', name: 'Đồ họa máy tính', total: 42, status: 'pending', statusText: 'Chưa nhập điểm' },
    { code: 'LTM218', name: 'Xử lý ảnh', total: 38, status: 'completed', statusText: 'Đã nhập điểm' },
    { code: 'LTM219', name: 'An toàn thông tin', total: 52, status: 'locked', statusText: 'Đã khóa điểm' },
    { code: 'LTM220', name: 'Quản trị mạng', total: 47, status: 'completed', statusText: 'Đã nhập điểm' },
    { code: 'LTM221', name: 'Lập trình di động', total: 55, status: 'pending', statusText: 'Chưa nhập điểm' },
    { code: 'LTM222', name: 'Phát triển ứng dụng web', total: 60, status: 'completed', statusText: 'Đã nhập điểm' },
    { code: 'LTM223', name: 'Kiểm thử phần mềm', total: 45, status: 'locked', statusText: 'Đã khóa điểm' },
    { code: 'LTM224', name: 'Quản lý dự án phần mềm', total: 50, status: 'completed', statusText: 'Đã nhập điểm' }
  ],
  
  chartData: [
    { range: '9-10', students: 63 },
    { range: '8-8.9', students: 77 },
    { range: '7-7.9', students: 50 },
    { range: '6-6.9', students: 48 },
    { range: '5-5.9', students: 23 },
    { range: '<5', students: 10 }
  ],
  
  warnings: [
    { student: 'Nguyễn Văn A', id: '22020203', classCode: 'LTM2023', reason: 'Đạt điểm chuyên cần < 80%' },
    { student: 'Trần Thị B', id: '22020204', classCode: 'LTM2023', reason: 'Có nguy cơ rớt môn' },
    { student: 'Lê Văn C', id: '22020205', classCode: 'LTM2024', reason: 'Vắng quá 30% buổi học' },
    { student: 'Phạm Thị D', id: '22020206', classCode: 'LTM2024', reason: 'Điểm giữa kỳ < 4.0' },
    // Thêm 20 cảnh báo mới
    { student: 'Hoàng Văn E', id: '22020207', classCode: 'LTM201', reason: 'Chưa nộp bài tập lớn' },
    { student: 'Bùi Thị F', id: '22020208', classCode: 'LTM202', reason: 'Điểm chuyên cần dưới 50%' },
    { student: 'Đỗ Văn G', id: '22020209', classCode: 'LTM203', reason: 'Vắng thi giữa kỳ' },
    { student: 'Vũ Thị H', id: '22020210', classCode: 'LTM204', reason: 'Điểm cuối kỳ < 3.0' },
    { student: 'Lý Văn I', id: '22020211', classCode: 'LTM205', reason: 'Không tham gia thảo luận nhóm' },
    { student: 'Đinh Thị K', id: '22020212', classCode: 'LTM206', reason: 'Đạo văn bài tập' },
    { student: 'Mai Văn L', id: '22020213', classCode: 'LTM207', reason: 'Vi phạm quy chế thi' },
    { student: 'Cao Thị M', id: '22020214', classCode: 'LTM208', reason: 'Điểm tổng kết < 4.0' },
    { student: 'Trịnh Văn N', id: '22020215', classCode: 'LTM209', reason: 'Không hoàn thành đồ án' },
    { student: 'Tô Thị O', id: '22020216', classCode: 'LTM210', reason: 'Thiếu bài tập thực hành' },
    { student: 'Phan Văn P', id: '22020217', classCode: 'LTM211', reason: 'Điểm kiểm tra thường xuyên thấp' },
    { student: 'Lâm Thị Q', id: '22020218', classCode: 'LTM212', reason: 'Không tham gia phòng lab' },
    { student: 'Hồ Văn R', id: '22020219', classCode: 'LTM213', reason: 'Nộp bài quá hạn nhiều lần' },
    { student: 'Ngô Thị S', id: '22020220', classCode: 'LTM214', reason: 'Chất lượng bài tập kém' },
    { student: 'Dương Văn T', id: '22020221', classCode: 'LTM215', reason: 'Không tương tác trên hệ thống E-learning' },
    { student: 'Đào Thị U', id: '22020222', classCode: 'LTM216', reason: 'Điểm đánh giá đồ án < 5.0' },
    { student: 'Chu Văn V', id: '22020223', classCode: 'LTM217', reason: 'Thiếu bài báo cáo' },
    { student: 'Vương Thị X', id: '22020224', classCode: 'LTM218', reason: 'Điểm thực hành không đạt' },
    { student: 'Lưu Văn Y', id: '22020225', classCode: 'LTM219', reason: 'Vắng quá 50% số buổi học' },
    { student: 'Trương Thị Z', id: '22020226', classCode: 'LTM220', reason: 'Không tham gia bài tập nhóm' }
  ]
};