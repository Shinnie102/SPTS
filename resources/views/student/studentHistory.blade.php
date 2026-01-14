<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- hạn chế đụng vào file overall.css -->
    <link rel="stylesheet" href="{{ asset('css/overall.css') }}">
    <link rel="stylesheet" href="{{ asset('css/student/studentHistory.css') }}">
    <!-- --------------------------------- -->
    <link
        href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400;500;600;700&family=Roboto:wght@100;300;400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/all.min.css') }}">
    <title>Lịch sử chuyên cần</title>
    <script>
        const attendanceData = @json($attendanceData);
    </script>
</head>

<body>
    <!-- Header -->
    @include('partials.header')
    <div id="main">
        <!-- Menu -->
        @include('student.menu_student')

        <div id="content">
            <!-- Vui lòng điểu chỉnh tiêu đề, không thay đổi tên id có sẵn -->
            <h1 id="tieudechinh">Lịch sử chuyên cần</h1>
            <p id="tieudephu">Theo dõi hình ảnh danh chi tiết theo từng môn học</p>
            <div id="student-alert-container"></div>

            <div class="history-container">
                <!-- ================= PROGRESS BAR SECTION ================= -->
                <div class="progress-section">
                    <h3 class="progress-header">Tổng quan chuyên cần toàn khóa</h3>
                    <p class="progress-subtitle">Tỷ lệ trung bình khóa học</p>
                    <div class="progress-bar-container">
                        <div class="progress-bar-wrapper">
                            <div class="progress-bar-fill" style="width: 0%"></div>
                        </div>
                        <span class="progress-percentage">0%</span>
                    </div>
                </div>

                <!-- ================= STATISTICS SECTION ================= -->
                <div class="statistics-section">
                    <!-- Header với dropdown -->
                    <div class="stats-header">
                        <div class="stats-header-left">
                            <h3>Thống kê theo môn học</h3>
                            <p>Nhấn vào hàng để xem chi tiết từng buổi học</p>
                        </div>
                        <div class="stats-header-right">
                            <select id="semester-dropdown" class="semester-dropdown">
                                <option value="2025-2026">HK hè 2025-2026</option>
                                <option value="2024-2025-2">HK 2 - 2024-2025</option>
                                <option value="2024-2025-1">HK 1 - 2024-2025</option>
                            </select>
                        </div>
                    </div>

                    <!-- Bảng thống kê -->
                    <div class="table-wrapper">
                        <table class="attendance-table">
                            <thead>
                                <tr>
                                    <th>Mã môn học</th>
                                    <th>Tên môn học</th>
                                    <th>Tổng số buổi</th>
                                    <th>Có mặt</th>
                                    <th>Vắng mặt</th>
                                    <th>Đi muộn</th>
                                    <th>Tỷ lệ</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody id="attendance-tbody">
                                <!-- JS sẽ fill dữ liệu vào đây -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Javascript -->
    <script src="{{ asset('js/student/student.js') }}"></script>
    <script src="{{ asset('js/student/studentAlertWarning.js') }}"></script>
    <script src="{{ asset('js/student/studentHistory.js') }}"></script>
</body>

</html>
