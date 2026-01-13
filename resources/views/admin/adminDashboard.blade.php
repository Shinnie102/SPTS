<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- hạn chế đụng vào file overall.css -->
    <link rel="stylesheet" href="{{ asset('css/overall.css') }}">
    <!-- --------------------------------- -->
    <link
        href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400;500;600;700&family=Roboto:wght@100;300;400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/adminDashboard.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <title>Trang chủ</title>
</head>

<body>
    <!-- Header -->
    <div class="header"></div>
    <div id="main">
        <!-- Menu -->
        <div class="menu_admin"></div>

        <div id="content">
            <!-- Vui lòng điểu chỉnh tiêu đề, không thay đổi tên id có sẵn -->
            <h1 id="tieudechinh">Trang chủ</h1>
            <p id="tieudephu">Giám sát toàn cảnh hệ thống quản lý học vụ</p>

            <!-- ====== THỐNG KÊ TỔNG QUAN ====== -->
            <div class="overview-cards" id="overviewCards">

                <!-- Tổng người dùng -->
                <div class="overview-card" id="card-total-users">
                    <div class="card-info">
                        <span id="label-total-users">Tổng người dùng</span>
                        <h2 id="value-total-users">-</h2>
                        <small id="desc-total-users">-</small>
                    </div>
                    <div class="card-icon">
                        <i class="fa-solid fa-users"></i>
                    </div>
                </div>

                <!-- Tổng lớp học phần -->
                <div class="overview-card" id="card-total-classes">
                    <div class="card-info">
                        <span id="label-total-classes">Tổng lớp học phần</span>
                        <h2 id="value-total-classes">-</h2>
                        <small id="desc-total-classes">-</small>
                    </div>
                    <div class="card-icon">
                        <i class="fa-solid fa-layer-group"></i>
                    </div>
                </div>

                <!-- Sinh viên cảnh báo -->
                <div class="overview-card" id="card-warning-students">
                    <div class="card-info">
                        <span id="label-warning-students">Sinh viên cảnh báo</span>
                        <h2 id="value-warning-students">-</h2>
                        <small id="desc-warning-students">-</small>
                    </div>
                    <div class="card-icon">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                </div>

                <!-- Lớp có vấn đề -->
                <div class="overview-card" id="card-problem-classes">
                    <div class="card-info">
                        <span id="label-problem-classes">Lớp có vấn đề</span>
                        <h2 id="value-problem-classes">-</h2>
                        <small id="desc-problem-classes">-</small>
                    </div>
                    <div class="card-icon">
                        <i class="fa-solid fa-circle-exclamation"></i>
                    </div>
                </div>

            </div>

            <!-- ====== BIỂU ĐỒ ====== -->
            <div class="chart-section">
                <!-- Cảnh báo hệ thống -->
                <div class="chart-card">
                    <div class="chart-header">
                        <div>
                            <h3>Cảnh báo hệ thống</h3>
                            <p>Các vấn đề cần được xử lý trong hệ thống học vụ</p>
                        </div>
                        <span class="badge">0 vấn đề cần xử lý</span>
                    </div>

                    <div class="alert-list">
                        <!-- JS sẽ render danh sách alerts vào đây -->
                    </div>
                </div>

                <!-- Phân bố nguyên nhân vấn đề -->
                <div class="chart-card">
                    <div class="chart-header">
                        <div>
                            <h3>Phân bố nguyên nhân vấn đề</h3>
                        </div>
                    </div>

                    <div class="chart-container">
                        <canvas id="problemCauseChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- ====== DANH SÁCH VẤN ĐỀ ====== -->
            <div class="issue-list-section">
                <h2>Danh sách các lớp có vấn đề cần xử lý</h2>
                <p>Học kỳ 1 (2024-2025)</p>
                <table class="issue-table">
                    <thead>
                        <tr>
                            <th>Mã lớp</th>
                            <th>Tên học phần</th>
                            <th>Số vấn đề</th>
                            <th>Mức độ</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- JS sẽ render danh sách lớp học vào đây -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Javascript -->
        <script src="{{ asset('js/admin/admin.js') }}"></script>
        <script src="{{ asset('js/admin/adminDashboard.js') }}"></script>
</body>

</html>