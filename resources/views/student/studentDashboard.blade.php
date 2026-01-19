<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- hạn chế đụng vào file overall.css -->
    <link rel="stylesheet" href="{{ asset('css/overall.css') }}">
    <link rel="stylesheet" href="{{ asset('css/student/studentDashboard.css') }}">

    <!-- --------------------------------- -->
    <link
        href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400;500;600;700&family=Roboto:wght@100;300;400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/all.min.css') }}">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <title>Trang chủ</title>
</head>

<body>
    <!-- Header -->
     @include('partials.header_student')
    <div id="main">
        <!-- Menu -->
        <div class="menu_student"></div>

        <div id="content">
            <!-- Vui lòng điểu chỉnh tiêu đề, không thay đổi tên id có sẵn -->
            <h1 id="tieudechinh">Tổng quan</h1>
            <p id="tieudephu">Xem tổng quan kết quả học tập và tình hình chuyên cần của bạn</p>
            <div id="student-alert-container"></div>

            <!-- ====== THỐNG KÊ TỔNG QUAN ====== -->
            <div class="overview-cards" id="overviewCards">

                <!-- GPA tích lũy -->
                <div class="overview-card" id="card-gpa-total">
                    <div class="card-info">
                        <span id="label-gpa-total">GPA tích lũy</span>
                        <h2 id="value-gpa-total">--</h2>
                        <small id="desc-gpa-total">Điểm trung bình toàn khóa</small>
                    </div>
                    <div class="card-icon">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </div>
                </div>

                <!-- GPA học kỳ -->
                <div class="overview-card" id="card-gpa-semester">
                    <div class="card-info">
                        <span id="label-gpa-semester">GPA học kỳ</span>
                        <h2 id="value-gpa-semester">--</h2>
                        <small id="desc-gpa-semester">Học kỳ 2 năm 2023</small>
                    </div>
                    <div class="card-icon">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                </div>

                <!-- Tín chỉ -->
                <div class="overview-card" id="card-credit">
                    <div class="card-info">
                        <span id="label-credit">Tín chỉ tích lũy</span>
                        <h2 id="value-credit">--</h2>
                        <small id="desc-credit">Còn 72 tín chỉ để tốt nghiệp</small>
                    </div>
                    <div class="card-icon">
                        <i class="fa-solid fa-book"></i>
                    </div>
                </div>

                <!-- Chuyên cần -->
                <div class="overview-card" id="card-attendance">
                    <div class="card-info">
                        <span id="label-attendance">Chuyên cần</span>
                        <h2 id="value-attendance">--</h2>
                        <small id="desc-attendance">Mức an toàn</small>
                    </div>
                    <div class="card-icon">
                        <i class="fa-solid fa-calendar-days"></i>
                    </div>
                </div>

            </div>


            <!-- ====== BIỂU ĐỒ ====== -->
            <div class="chart-section">
                <div class="chart-card">
                    <div class="chart-header">
                        <div>
                            <h3>Biểu đồ GPA</h3>
                            <p>Theo dõi sự tiến bộ trong học tập của bạn</p>
                        </div>
                        <select id="gpa-semester-select">
                            <option value="HK1-2025-2026">HK hè 2025-2026</option>
                            <option value="HK2-2025-2026" selected>HK 2 2025-2026</option>
                            <option value="HK1-2024-2025">HK 1 2024-2025</option>
                        </select>
                    </div>
                    <div class="chart-container">
                        <canvas id="gpaChart"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-header">
                        <div>
                            <h3>Tỷ lệ chuyên cần theo môn</h3>
                            <p>Thống kê điểm danh học kỳ</p>
                        </div>
                        <select id="attendance-semester-select">
                            <option value="HK1-2025-2026">HK hè 2025-2026</option>
                            <option value="HK2-2025-2026" selected>HK 2 2025-2026</option>
                            <option value="HK1-2024-2025">HK 1 2024-2025</option>
                        </select>
                    </div>
                    <div class="chart-container">
                        <canvas id="attendanceChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- ====== TRUY CẬP NHANH ====== -->
            <div class="quick-access">
                <h3>Truy cập nhanh</h3>
                <p>Xem chi tiết các thông tin học tập của bạn</p>

                <div class="quick-cards">
                    <div class="quick-card">
                        <div class="quick-icon blue">
                            <i class="fa-solid fa-book-open"></i>
                        </div>
                        <div class="quick-info">
                            <strong>Xem điểm môn học</strong>
                            <span>Chi tiết từng môn học</span>
                        </div>
                        <i class="fa-solid fa-arrow-right"></i>
                    </div>

                    <div class="quick-card">
                        <div class="quick-icon blue">
                            <i class="fa-solid fa-calendar-days"></i>
                        </div>
                        <div class="quick-info">
                            <strong>Lịch sử chuyên cần</strong>
                            <span>Thống kê điểm danh</span>
                        </div>
                        <i class="fa-solid fa-arrow-right"></i>
                    </div>
                </div>
            </div>


        </div>
    </div>

    <!-- Javascript -->
    <script src="{{ asset('js/student/student.js') }}"></script>
    <script src="{{ asset('js/student/studentAlertWarning.js') }}"></script>
    <script src="{{ asset('js/student/studentCharts.js') }}"></script>
    <script src="{{ asset('js/student/studentDashboard.js') }}"></script>
</body>

</html>
