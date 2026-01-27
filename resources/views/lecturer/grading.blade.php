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
    <link rel="stylesheet" href="{{ asset('css/lecturer/dropdown-header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/lecturer/grading.css') }}">
    <link rel="stylesheet" href="{{ asset('css/lecturer/attendance.css') }}">
    <!-- --------------------------------- -->
    <link
        href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400;500;600;700&family=Roboto:wght@100;300;400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <title>PointC - Nhập điểm lớp học phần</title>
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
                <div class="attendance-container">

                <!-- Khối thông báo Khóa dữ liệu (đồng bộ y hệt Attendance) -->
                <div id="attendance-lock-notice" class="lock-status-container" style="display:none;">
                    <div class="lock-header-row">
                        <div>
                            <h2 class="lock-main-title">Khóa dữ liệu lớp học</h2>
                            <p class="lock-description">Lớp đã ở trạng thái Đã hoàn thành hoặc Đã hủy. Bạn chỉ có thể xem, không thể chỉnh sửa.</p>
                        </div>
                        <div class="lock-status-icon">
                            <img src="{{ asset('lecturer/img/lock-gray.svg') }}" alt="Khóa">
                        </div>
                    </div>
                    <div class="lock-warning-banner">
                        <div class="warning-icon-circle">
                            <img src="{{ asset('lecturer/img/warning-icon.png') }}" alt="Cảnh báo">
                        </div>
                        <div class="warning-text-box">
                            <h3 class="warning-heading" style="color: #FEBC2F;">Không thể sửa dữ liệu</h3>
                            <p class="warning-detail" style="color: #FEBC2F;">Dữ liệu của lớp đã được khóa (Đã hoàn thành/Đã hủy). Để chỉnh sửa, vui lòng liên hệ quản trị viên.</p>
                        </div>
                    </div>
                </div>

                
@include('lecturer.attendance_header', [
    'currentClass' => $currentClass,
    'classes' => $classes,
    'currentTab' => 'grading'
])

                    <div class="filter-group">
                        <label>Cấu trúc điểm</label>
                    </div>

                    <!-- Bảng Cấu trúc điểm (data-driven) -->
                    <div class="structure-frame" id="structureTable">
                        <!-- Sẽ được render bằng JavaScript -->
                    </div>

                    <div class="filter-group">
                        <label>Nhập điểm</label>
                    </div>

                    <!-- Bảng Nhập điểm (data-driven) -->
                    <div class="grade-table-frame" id="gradeTable">
                        <!-- Sẽ được render bằng JavaScript -->
                    </div>

                    <!-- Link quay lại -->
                    <div class="back-link-container">
                        <a href="{{ route('lecturer.dashboard') }}" class="back-link">
                            <i class="fas fa-arrow-left"></i> Quay lại trang chủ
                        </a>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Javascript -->
    <script src="{{ asset('js/lecturer/dataGrading.js') }}"></script>
    <script src="{{ asset('js/lecturer/grading.js') }}"></script>
    <script src="{{ asset('js/lecturer/dropdown-header.js') }}"></script>
</body>
</html>