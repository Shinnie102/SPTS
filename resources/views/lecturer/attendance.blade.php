<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- hạn chế đụng vào file overall.css -->
    <link rel="stylesheet" href="{{ asset('css/lecturer/styleL.css') }}">
    <link rel="stylesheet" href="{{ asset('css/lecturer/styleClass.css') }}">
    <link rel="stylesheet" href="{{ asset('css/overall.css') }}">
    <link rel="stylesheet" href="{{ asset('css/lecturer/attendance.css') }}">
    <!-- --------------------------------- -->
    <link
        href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400;500;600;700&family=Roboto:wght@100;300;400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <title>PointC - Điểm danh lớp học phần</title>
</head>

<body>
    <!-- Header -->
    @include('partials.header_lecturer')

    <div id="main">
        <!-- Menu -->
        @include('lecturer.menu_lecturer')

        <div id="content">
            <!-- Vui lòng điểu chỉnh tiêu đề, không thay đổi tên id có sẵn -->
            <h1 id="tieudechinh">Danh sách lớp học phần</h1>
            <p id="tieudephu">Quản lý và theo dõi các lớp học của bạn</p>

            <main class="main-content">
                <!-- Attendance Container -->
                    @include('lecturer.attendance_header')

                    <!-- Hàng 3: Buổi điểm danh + Stats + Nút Lưu -->
                    <div class="action-row">
                        <!-- Buổi điểm danh -->
                        <div class="session-filter">
                            <label for="session-select">Buổi điểm danh</label>
                            <div class="select-wrapper">
                                <select id="session-select">
                                    <option value="session1">Buổi 1 - Ngày 12/11/2025</option>
                                    <option value="session2">Buổi 2 - Ngày 19/11/2025</option>
                                    <option value="session3">Buổi 3 - Ngày 26/11/2025</option>
                                </select>
                                <div class="select-arrow">▼</div>
                            </div>
                        </div>

                        <!-- Stats - Giữ nguyên format cũ -->
                        <div class="attendance-stats">
                            <div class="stat-box">
                                <span>Có mặt:</span>
                                <span data-count="present">0</span>
                            </div>
                            <div class="stat-box">
                                <span>Vắng:</span>
                                <span data-count="absent">0</span>
                            </div>
                            <div class="stat-box">
                                <span>Vắng có phép:</span>
                                <span data-count="excused">0</span>
                            </div>
                            <div class="stat-box">
                                <span>Tổng số:</span>
                                <span data-count="total">0</span>
                            </div>
                        </div>

                        <!-- Nút Lưu -->
                        <div class="save-btn-wrapper">
                            <button id="save-attendance-btn" class="save-btn">
                                <span> Lưu điểm danh</span>
                            </button>
                        </div>
                    </div>

                    <!-- Bảng điểm danh với thanh cuộn -->
                    <div class="attendance-table-container">
                        <div class="attendance-table">
                            <div class="table-header">
                                <div>STT</div>
                                <div>Tên sinh viên</div>
                                <div>Mã số SV</div>
                                <div>Trạng thái</div>
                            </div>
                            
                            <div id="attendance-table-body">
                                <!-- Danh sách sinh viên sẽ được render ở đây bằng JavaScript -->
                            </div>
                        </div>
                    </div>

                    <!-- Link quay lại -->
                    <div class="back-link-container">
                        <a href="{{ route('lecturer.dashboard') }}" class="back-link">
                            <i class="fas fa-arrow-left"></i> Quay lại trang chủ
                        </a>
                    </div>
                
            </main>
        </div>
    </div>

    <!-- Javascript -->
    <script src="{{ asset('js/lecturer/dataAttendance.js') }}"></script>
    <script src="{{ asset('js/lecturer/attendance.js') }}"></script>
</body>
</html>