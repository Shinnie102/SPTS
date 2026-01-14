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
    <link rel="stylesheet" href="{{ asset('css/lecturer/classStatus.css') }}">
    <!-- --------------------------------- -->
    <link
        href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400;500;600;700&family=Roboto:wght@100;300;400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <title>PointC - Trạng thái lớp học phần</title>
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
                    <div class="class-row">
                        <div class="filter-group">
                            <label for="class-select">Lớp học phần</label>
                            <div class="select-wrapper">
                                <select id="class-select">
                                    <option value="LTM101">LTM101 - Lập trình mạng</option>
                                    <option value="CS101">CS101 - Cơ sở dữ liệu</option>
                                    <option value="WEB101">WEB101 - Lập trình Web</option>
                                </select>
                                <div class="select-arrow">▼</div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-navigation-row">
                        <nav class="tab-navigation">
                            <a href="{{ route('lecturer.attendance.show') }}" class="tab-item">Điểm danh</a>
                            <a href="{{ route('lecturer.grading.show') }}" class="tab-item">Nhập điểm</a>
                            <a href="{{ route('lecturer.classes.show') }}" class="tab-item active">Trạng thái lớp</a>
                            <a href="{{ route('lecturer.report.show') }}" class="tab-item">Báo cáo</a>
                        </nav>
                    </div>

                    <div class="dashboard-container">
                        <div class="row-top">
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="card-title">Trạng thái điểm danh</h2>
                                    <div class="icon success">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                            <circle cx="12" cy="12" r="10" />
                                            <path d="M8 12L11 15L16 9" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="info-row"><span>Số buổi điểm danh:</span><strong>12/15</strong></div>
                                    <div class="info-row"><span>Tỉ lệ hoàn thành:</span><strong>80%</strong></div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header">
                                    <h2 class="card-title">Trạng thái nhập điểm</h2>
                                    <div class="icon success">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                            <circle cx="12" cy="12" r="10" />
                                            <path d="M8 12L11 15L16 9" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="info-row"><span>Đã nhập điểm:</span><strong>42/42</strong></div>
                                    <div class="info-row"><span>Tỉ lệ hoàn thành:</span><strong>80%</strong></div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header">
                                    <h2 class="card-title">Thông tin cập nhật</h2>
                                </div>
                                <div class="card-body">
                                    <div class="info-row"><span>Lần cập nhật cuối:</span><strong>06/01/2026 14:30</strong></div>
                                    <div class="info-row"><span>Người cập nhật:</span><strong>Nguyen Van A</strong></div>
                                    <div class="info-row">
                                        <span>Trạng thái lớp:</span><strong class="status-active">Đang hoạt động</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row-bottom">
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="card-title">Khóa dữ liệu lớp học</h2>
                                    <div class="icon-lock-gray">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="2">
                                            <rect x="5" y="11" width="14" height="10" rx="2" />
                                            <path d="M8 11V7a4 4 0 1 1 8 0v4" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="card-body action-layout">
                                    <p class="note">
                                        Lưu ý: Khi khóa dữ liệu lớp, bạn sẽ không thể chỉnh sửa điểm danh hoặc điểm số. Vui lòng đảm bảo tất cả thông tin đã chính xác trước khi khóa.
                                    </p>
                                    <button class="btn btn-red">
                                        <svg width="14" height="14" fill="white" viewBox="0 0 24 24">
                                            <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6z" />
                                        </svg>
                                        Khóa
                                    </button>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header">
                                    <h2 class="card-title">Xuất bảng điểm</h2>
                                    <div class="icon-download-gray">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="2" stroke-linecap="round">
                                            <circle cx="12" cy="12" r="10" />
                                            <path d="M12 8v8m-4-4l4 4 4-4" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="card-body action-layout">
                                    <p class="note">Xuất bảng điểm danh và bảng điểm của lớp dưới dạng file Excel hoặc PDF.</p>
                                    <button class="btn btn-dark-blue">
                                        <svg width="14" height="14" fill="white" viewBox="0 0 24 24">
                                            <path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z" />
                                        </svg>
                                        Xuất
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

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
    <script src="{{ asset('js/lecturer/dataAttendance.js') }}"></script>
    <script src="{{ asset('js/lecturer/attendance.js') }}"></script>
</body>
</html>