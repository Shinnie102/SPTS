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
                <section id="lock-notification" class="lock-status-container" style="display: none;">
                    <div class="lock-header-row">
                        <div class="lock-text-group">
                            <h3 class="lock-main-title">Khóa dữ liệu lớp học</h3>
                            <p class="lock-description">Khóa dữ liệu để ngăn chỉnh sửa điểm danh và điểm số</p>
                        </div>
                        <div class="lock-status-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                    </div>

                    <div class="lock-warning-banner">
                        <div class="warning-icon-circle">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="warning-text-box">
                            <h4 class="warning-heading">Dữ liệu đã được khóa</h4>
                            <p class="warning-detail">Sau khi khóa, bạn không thể chỉnh sửa điểm danh và điểm số. Vui lòng liên hệ quản trị viên nếu cần mở khóa.</p>
                        </div>
                    </div>
                </section>

                
                    @include('lecturer.attendance_header')


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
                
            </main>
        </div>
    </div>

    <!-- Javascript -->
    <script src="{{ asset('js/lecturer/dataGrading.js') }}"></script>
    <script src="{{ asset('js/lecturer/grading.js') }}"></script>
    <script src="{{ asset('js/lecturer/attendance.js') }}"></script>

    <script>
        function checkClassLockStatus(classCode) {
            // 1. Tìm thông tin lớp trong mockData (giả sử dữ liệu nằm trong biến mockData)
            const currentClass = mockData.classes.find(c => c.code === classCode);
            
            const lockBox = document.getElementById('lock-notification');
            const saveBtn = document.getElementById('save-attendance-btn');
            const attendanceTable = document.getElementById('attendance-table-body');

            if (currentClass && currentClass.status === 'locked') {
                // TRƯỜNG HỢP: LỚP BỊ KHÓA
                lockBox.style.display = 'block'; // Hiện thông báo
                
                if (saveBtn) {
                    saveBtn.disabled = true; // Vô hiệu hóa nút lưu
                    saveBtn.style.opacity = '0.5';
                    saveBtn.style.cursor = 'not-allowed';
                    saveBtn.innerText = 'Dữ liệu đã khóa';
                }

                // Ngăn không cho click vào các radio/checkbox điểm danh
                if (attendanceTable) {
                    attendanceTable.style.pointerEvents = 'none'; 
                    attendanceTable.style.opacity = '0.8';
                }
            } else {
                // TRƯỜNG HỢP: LỚP ĐANG MỞ
                lockBox.style.display = 'none'; // Ẩn thông báo
                
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.style.opacity = '1';
                    saveBtn.style.cursor = 'pointer';
                    saveBtn.innerText = 'Lưu điểm danh';
                }

                if (attendanceTable) {
                    attendanceTable.style.pointerEvents = 'auto';
                    attendanceTable.style.opacity = '1';
                }
            }
        }
    </script>
</body>
</html>