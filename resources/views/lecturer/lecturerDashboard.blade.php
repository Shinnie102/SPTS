<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- hạn chế đụng vào file overall.css -->
    <link rel="stylesheet" href="{{ asset('css/overall.css') }}">
    <link rel="stylesheet" href="{{ asset('css/lecturer/styleL.css') }}">

    <!-- --------------------------------- -->
    <link
        href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400;500;600;700&family=Roboto:wght@100;300;400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <title>Trang tổng quan giảng viên</title>
</head>

<body>
    <!-- Header -->
    @include('partials.header_lecturer')

    <div id="main">
        <!-- Menu -->
        @include('lecturer.menu_lecturer')

        <div id="content">
            <!-- Vui lòng điểu chỉnh tiêu đề, không thay đổi tên id có sẵn -->
            <h1 id="tieudechinh">Tổng quan</h1>
            <p id="tieudephu">Xem tổng hợp thông tin về các lớp học và cảnh báo</p>

            <main class="main-content">
                <!-- Statistics Cards Section -->
                <section class="stats-section" aria-label="Statistics overview">
                    <!-- Card 1: Lớp phụ trách -->
                    <article class="stat-card">
                        <div class="stat-color-bar"></div>
                        <h2 class="stat-title">Lớp phụ trách</h2>
                        <p class="stat-number" data-stat="totalClasses">25</p>
                        <p class="stat-subtitle">Đang giảng dạy</p>
                        <img class="stat-icon" src="{{ asset('lecturer/img/vector-4.svg') }}" alt="Lớp học icon" />
                    </article>

                    <!-- Card 2: Cảnh báo -->
                    <article class="stat-card">
                        <div class="stat-color-bar warning"></div>
                        <h2 class="stat-title">Cảnh báo</h2>
                        <p class="stat-number" data-stat="warnings">8</p>
                        <p class="stat-subtitle">Sinh viên có nguy cơ học vụ</p>
                        <img class="stat-icon" src="{{ asset('lecturer/img/vector-5.svg') }}" alt="Cảnh báo icon" />
                    </article>

                    <!-- Card 3: Nhập điểm -->
                    <article class="stat-card">
                        <div class="stat-color-bar"></div>
                        <h2 class="stat-title">Nhập điểm</h2>
                        <p class="stat-number" data-stat="completedGrading">12</p>
                        <p class="stat-subtitle">Lớp hoàn tất nhập điểm</p>
                        <img class="stat-icon" src="{{ asset('lecturer/img/vector-6.svg') }}" alt="Nhập điểm icon" />
                    </article>

                    <!-- Card 4: Cần nhập điểm -->
                    <article class="stat-card warning-accent">
                        <div class="stat-color-bar warning"></div>
                        <h2 class="stat-title warning">Cần nhập điểm</h2>
                        <p class="stat-number warning" data-stat="pendingGrading">7</p>
                        <p class="stat-subtitle">Lớp sắp đến hạn.</p>
                        <img class="stat-icon" src="{{ asset('lecturer/img/vector-7.svg') }}" alt="Cần nhập điểm icon" />
                    </article>
                </section>

                <!-- Class List Table -->
                <section class="table-section" aria-labelledby="class-list-heading">
                    <div class="table-header-content">
                        <h3 class="table-title" id="class-list-heading">Danh sách lớp học phần</h3>
                        <p class="table-subtitle">Quản lý và theo dõi các lớp học hôm nay của bạn</p>
                    </div>

                    <div class="table-wrapper">
                        <table class="class-table" role="table">
                            <thead>
                                <tr role="row">
                                    <th role="columnheader">Mã môn học</th>
                                    <th role="columnheader">Tên môn học</th>
                                    <th role="columnheader">Tổng số</th>
                                    <th role="columnheader">Trạng thái</th>
                                    <th role="columnheader">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="class-table-body">
                                <!-- Rows will be rendered by JavaScript (chỉ 3 lớp) -->
                            </tbody>
                        </table>
                    </div>

                    <a href="{{ route('lecturer.classes') }}" class="view-more">Xem thêm →</a>
                </section>

                <!-- Charts Section -->
                <section class="charts-container">
                    <!-- Chart 1 - Bar Chart -->
                    <section class="chart-section" aria-labelledby="chart-heading">
                        <h3 class="chart-title" id="chart-heading">Biểu đồ phân bổ điểm</h3>
                        <p class="chart-subtitle">Xem phân bổ điểm của sinh viên</p>
                        <div class="bar-chart-container">
                            <main class="bar-line-chart" role="main">
                                <section class="chart-axis" aria-label="Chart visualization">
                                    <div class="main-chart">
                                        <aside class="y-axis-left" role="list" aria-label="Y-axis values">
                                            <span class="y-axis-label" role="listitem" aria-label="Y-axis value 100">100</span>
                                            <span class="y-axis-label" role="listitem" aria-label="Y-axis value 80">80</span>
                                            <span class="y-axis-label" role="listitem" aria-label="Y-axis value 60">60</span>
                                            <span class="y-axis-label" role="listitem" aria-label="Y-axis value 40">40</span>
                                            <span class="y-axis-label" role="listitem" aria-label="Y-axis value 20">20</span>
                                            <span class="y-axis-label" role="listitem" aria-label="Y-axis value 0">0</span>
                                        </aside>
                                        <div class="graphi-grid" role="img" aria-label="Bar chart showing student distribution by grade ranges">
                                            <div class="x-lines" aria-hidden="true">
                                                <span class="grid-line"></span>
                                                <span class="grid-line"></span>
                                                <span class="grid-line"></span>
                                                <span class="grid-line"></span>
                                                <span class="grid-line"></span>
                                                <span class="grid-line"></span>
                                            </div>
                                            <div class="y-lines" aria-hidden="true">
                                                <span class="grid-line-vertical"></span>
                                                <span class="grid-line-vertical"></span>
                                                <span class="grid-line-vertical"></span>
                                                <span class="grid-line-vertical"></span>
                                                <span class="grid-line-vertical"></span>
                                                <span class="grid-line-vertical"></span>
                                                <span class="grid-line-vertical"></span>
                                            </div>
                                            <svg class="bar-area" id="chart-bars" viewBox="0 0 100 100" preserveAspectRatio="none" role="presentation" aria-hidden="true">
                                                <!-- Bars will be rendered by JavaScript -->
                                            </svg>
                                        </div>
                                    </div>
                                    <nav class="x-axis" role="list" aria-label="X-axis grade ranges">
                                        <div class="x-label-box" role="listitem"><span class="x-axis-label" data-range="9-10">9-10</span></div>
                                        <div class="x-label-box" role="listitem"><span class="x-axis-label" data-range="8-8.9">8-8.9</span></div>
                                        <div class="x-label-box" role="listitem"><span class="x-axis-label" data-range="7-7.9">7-7.9</span></div>
                                        <div class="x-label-box" role="listitem"><span class="x-axis-label" data-range="6-6.9">6-6.9</span></div>
                                        <div class="x-label-box" role="listitem"><span class="x-axis-label" data-range="5-5.9">5-5.9</span></div>
                                        <div class="x-label-box" role="listitem"><span class="x-axis-label" data-range="<5">&lt;5</span></div>
                                    </nav>
                                </section>
                                <footer class="legends" role="list" aria-label="Chart legend">
                                    <div class="fill-legends">
                                        <div class="legend" role="listitem">
                                            <div class="legend-node" aria-hidden="true">
                                                <div class="basic-node"><span class="square-fill"></span></div>
                                            </div>
                                            <span class="legend-text">Số sinh viên</span>
                                        </div>
                                    </div>
                                </footer>
                            </main>
                        </div>
                    </section>

                    <!-- Warnings Section -->
                    <section class="warnings-section" aria-labelledby="warning-heading">
                        <!-- Thêm liên kết vào tiêu đề cảnh báo -->
                            <h3 class="warnings-title" id="warning-heading">Cảnh báo học vụ</h3>
                            <p class="warnings-subtitle">Cảnh báo sớm sinh viên có nguy cơ</p>
                        </a>

                        <div class="warnings-list" id="warnings-list">
                            <!-- Warning items will be rendered by JavaScript (chỉ 4 cảnh báo) -->
                        </div>
                    </section>
                </section>
            </main>
        </div>
    </div>

    <!-- Javascript -->
    <script src="{{ asset('js/lecturer/lecturer.js') }}"></script>
    <script src="{{ asset('js/lecturer/data.js') }}"></script>
    <script src="{{ asset('js/lecturer/render.js') }}"></script>

</body>

</html>
