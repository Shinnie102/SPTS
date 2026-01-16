<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- hạn chế đụng vào file overall.css -->
    <link rel="stylesheet" href="{{ asset('css/overall.css') }}">
    <link rel="stylesheet" href="{{ asset('css/student/studentStudy.css') }}">
    <!-- --------------------------------- -->
    <link
        href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400;500;600;700&family=Roboto:wght@100;300;400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/all.min.css') }}">
    <title>Học tập</title>
    <script>
        // Inject data từ backend vào JavaScript
        const scoreData = @json($scoreData);
        console.log('=== SCORE DATA FROM BACKEND ===');
        console.log('scoreData:', scoreData);
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
            <h1 id="tieudechinh">Kết quả học tập</h1>
            <p id="tieudephu">Theo dõi điểm chi tiết và bảng điểm tổng hợp</p>
            <div id="student-alert-container"></div>
            <div class="study-container">

                <!-- Tabs -->
                <div class="study-tabs">
                    <button class="tab-btn active" data-tab="detail">
                        <span>Chi tiết học kì</span>
                    </button>
                    <button class="tab-btn" data-tab="summary">
                        <span>Bảng điểm toàn khóa</span>
                    </button>
                </div>

                <!-- ================= TAB CHI TIẾT HỌC KÌ ================= -->
                <div id="tab-detail" class="tab-content active">
                    <div class="detail-section">
                        <!-- Header với dropdown -->
                        <div class="detail-header">
                            <div class="header-left">
                                <h3 class="section-title">Bảng điểm chi tiết</h3>
                                <p class="section-subtitle">Chi tiết điểm thành phần của các môn trong học kỳ đã chọn</p>
                            </div>
                            <div class="header-right">
                                <select id="semester-dropdown" class="semester-dropdown">
                                    <!-- JavaScript sẽ populate options từ backend data -->
                                </select>
                            </div>
                        </div>

                        <!-- Bảng điểm chi tiết -->
                        <div class="table-wrapper">
                            <table class="detail-table">
                                <thead>
                                    <tr>
                                        <!-- JavaScript sẽ render header động dựa trên grading components -->
                                    </tr>
                                </thead>
                                <tbody id="detail-tbody">
                                    <!-- JS sẽ fill dữ liệu vào đây -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB BẢNG ĐIỂM TOÀN KHÓA ================= -->
                <div id="tab-summary" class="tab-content">
                    <!-- Thông tin tổng quan toàn khóa -->
                    <div class="overview-cards">
                        <div class="card">
                            <div class="card-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                            <p class="label">GPA tích lũy</p>
                            <h2 id="total-gpa" class="stat-value">{{ number_format($scoreData['summary']['gpa'] ?? 0, 2) }}</h2>
                            <p class="description">Điểm trung bình tích lũy toàn khóa</p>
                        </div>
                        <div class="card">
                            <div class="card-icon"><i class="fa-solid fa-book-open"></i></div>
                            <p class="label">Tín chỉ tích lũy</p>
                            <h2 id="total-credits" class="stat-value">{{ $scoreData['summary']['total_credits'] ?? 0 }}</h2>
                            <p class="description">Gồm {{ $scoreData['summary']['passed_credits'] ?? 0 }} tín chỉ đã đạt</p>
                        </div>
                        <div class="card">
                            <div class="card-icon"><i class="fa-solid fa-bullseye"></i></div>
                            <p class="label">Tiến độ</p>
                            <h2 id="total-progress" class="stat-value">{{ number_format($scoreData['summary']['progress'] ?? 0, 1) }}%</h2>
                            <p class="description">Còn {{ number_format(100 - ($scoreData['summary']['progress'] ?? 0), 1) }}% để tốt nghiệp</p>
                        </div>
                    </div>

                    <!-- Danh sách học kỳ -->
                    <div id="semester-list">
                        <!-- JS sẽ tạo các section học kỳ động -->
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="{{ asset('js/student/student.js') }}"></script>
    <script src="{{ asset('js/student/studentStudy.js') }}"></script>
    <script src="{{ asset('js/student/studentAlertWarning.js') }}"></script>
</body>

</html>