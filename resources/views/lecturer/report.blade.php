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
    <link rel="stylesheet" href="{{ asset('css/lecturer/report.css') }}">
    <!-- --------------------------------- -->
    <link
        href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400;500;600;700&family=Roboto:wght@100;300;400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <title>PointC - Báo cáo lớp học phần</title>
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
                @include('lecturer.attendance_header')

                    <section class="charts-container">
                        <section class="chart-section">
                            <h3 class="chart-title">Biểu đồ phân bổ điểm</h3>
                            <p class="chart-subtitle">Xem phân bổ điểm của sinh viên</p>
                            <div class="bar-chart-container">
                                <main class="bar-line-chart">
                                    <section class="chart-axis">
                                        <div class="main-chart">
                                            <aside class="y-axis-left">
                                                <span>100</span><span>80</span><span>60</span><span>40</span><span>20</span><span>0</span>
                                            </aside>
                                            <div class="graphi-grid">
                                                <div class="x-lines"><span class="grid-line"></span><span class="grid-line"></span><span class="grid-line"></span><span class="grid-line"></span><span class="grid-line"></span><span class="grid-line"></span></div>
                                                <svg class="bar-area" id="chart-bars" viewBox="0 0 100 100" preserveAspectRatio="none"></svg>
                                            </div>
                                        </div>
                                        <nav class="x-axis">
                                            <div class="x-label-box"><span>9-10</span></div>
                                            <div class="x-label-box"><span>8-8.9</span></div>
                                            <div class="x-label-box"><span>7-7.9</span></div>
                                            <div class="x-label-box"><span>6-6.9</span></div>
                                            <div class="x-label-box"><span>5-5.9</span></div>
                                            <div class="x-label-box"><span>&lt;5</span></div>
                                        </nav>
                                    </section>
                                </main>
                            </div>
                        </section>

                        <section class="chart-section">
                            <h3 class="chart-title">Tỷ lệ đạt / không đạt</h3>
                            <p class="chart-subtitle">Thống kê kết quả học tập của sinh viên</p>
                            <div class="pie-chart-wrapper">
                                <div class="pie-chart-container">
                                    <svg class="donut-chart" viewBox="0 0 42 42">
                                        <circle class="donut-ring" cx="21" cy="21" r="15.915" fill="transparent" stroke="#FF9B8E" stroke-width="6"></circle>
                                        <circle class="donut-segment" cx="21" cy="21" r="15.915" fill="transparent" stroke="#8B79FF" stroke-width="6" stroke-dasharray="96 4" stroke-dashoffset="25"></circle>
                                        <text x="21" y="21" class="chart-number">62</text>
                                    </svg>
                                </div>
                                <div class="pie-legend">
                                    <div class="legend-item"><span class="legend-dot purple"></span><span>Đạt</span></div>
                                    <div class="legend-item"><span class="legend-dot coral"></span><span>Không đạt</span></div>
                                </div>
                            </div>
                        </section>
                    </section>

                    <section class="warning-table-section">
                        <div class="warning-header">
                            <i class="fas fa-exclamation-triangle warning-icon-img"></i>
                            <h3 class="warning-title">Danh sách sinh viên có cảnh báo học vụ</h3>
                        </div>
                        <div class="table-container">
                            <table class="styled-table">
                                <thead>
                                    <tr>
                                        <th>MSSV</th>
                                        <th>HỌ VÀ TÊN</th>
                                        <th>LỚP HỌC PHẦN</th>
                                        <th>TỔNG</th>
                                        <th>TRẠNG THÁI</th>
                                        <th>THAO TÁC</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="bold-text">SV001</td>
                                        <td>Nguyễn Văn A</td>
                                        <td>CS101_01</td>
                                        <td class="score-danger">4.5</td>
                                        <td class="status-danger">Nguy cơ cao</td>
                                        <td><a href="#" class="view-detail"><i class="fas fa-eye"></i> Xem chi tiết</a></td>
                                    </tr>
                                    <tr>
                                        <td class="bold-text">SV002</td>
                                        <td>Trần Thị B</td>
                                        <td>CS102_02</td>
                                        <td class="score-danger">4.8</td>
                                        <td class="status-danger">Nguy cơ cao</td>
                                        <td><a href="#" class="view-detail"><i class="fas fa-eye"></i> Xem chi tiết</a></td>
                                    </tr>
                                    <tr>
                                        <td class="bold-text">SV003</td>
                                        <td>Lê Văn C</td>
                                        <td>CS201_01</td>
                                        <td class="score-warning">5.2</td>
                                        <td class="status-warning">Cần theo dõi</td>
                                        <td><a href="#" class="view-detail"><i class="fas fa-eye"></i> Xem chi tiết</a></td>
                                    </tr>
                                    <tr>
                                        <td class="bold-text">SV004</td>
                                        <td>Phạm Thị D</td>
                                        <td>CS101_01</td>
                                        <td class="score-warning">5.4</td>
                                        <td class="status-warning">Cần theo dõi</td>
                                        <td><a href="#" class="view-detail"><i class="fas fa-eye"></i> Xem chi tiết</a></td>
                                    </tr>
                                    <tr>
                                        <td class="bold-text">SV005</td>
                                        <td>Hoàng Văn E</td>
                                        <td>CS202_03</td>
                                        <td class="score-danger">4.2</td>
                                        <td class="status-danger">Nguy cơ cao</td>
                                        <td><a href="#" class="view-detail"><i class="fas fa-eye"></i> Xem chi tiết</a></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <div class="back-link-container">
                        <a href="{{ route('lecturer.dashboard') }}" class="back-link">
                            <i class="fas fa-arrow-left"></i> Quay lại trang chủ
                        </a>
                    </div>
                
            </main>
        </div>
    </div>

    <!-- Javascript -->
    <script src="{{ asset('js/lecturer/data.js') }}"></script>
    <script src="{{ asset('js/lecturer/render.js') }}"></script>
    <script src="{{ asset('js/lecturer/attendance.js') }}"></script>
</body>
</html>