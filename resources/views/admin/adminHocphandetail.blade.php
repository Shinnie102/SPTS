<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="route-admin-lophoc-api-show" content="{{ route('admin.lophoc.api.show', ['id' => '__ID__']) }}">
    <meta name="route-admin-lophoc-api-delete-enrollment" content="{{ route('admin.lophoc.api.enrollment.delete', ['id' => '__ENROLLMENT__']) }}">
    <meta name="class-section-id" content="{{ request()->route('id') }}">
    <!-- hạn chế đụng vào file overall.css -->
    <link rel="stylesheet" href="{{ asset('css/overall.css') }}">
    <!-- --------------------------------- -->
    <link rel="stylesheet" href="{{ asset('css/admin/adminHocphandetail.css') }}">
    <link
        href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400;500;600;700&family=Roboto:wght@100;300;400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/all.min.css') }}">
    <title>Chi tiết học phần</title>
</head>

<body>
    <!-- Header -->
    @include('partials.header_admin')
    <div id="main">
        <!-- Menu -->
        @include('admin.menu_admin')

        <div id="content">
            <!-- Vui lòng điểu chỉnh tiêu đề, không thay đổi tên id có sẵn -->
            <h1 id="tieudechinh">Chi tiết lớp học phần</h1>
            <p id="tieudephu">Thông tin chi tiết về lớp học phần</p>
            
            <!-- Toast notification -->
            <div id="toast" style="display:none; position:fixed; top:20px; right:20px; padding:12px 16px; border-radius:6px; color:#fff; z-index:9999; box-shadow:0 4px 12px rgba(0,0,0,0.15); font-weight:500;"></div>

            <!-- ------------------------------------------------ -->
            <!-- Nội dung riêng của từng trang sẽ được chèn vào đây -->

            <div id="top">
                <div id="title-top">
                    <p>Thông tin lớp học phần</p>
                    <a href="{{ route('admin.lophoc.edit.step1', ['id' => request()->route('id')]) }}" style="text-decoration: none;">
                        <button id="edit"><i class="fa-solid fa-pen"></i> Chỉnh sửa</button>
                    </a>
                </div>
                <div class="frame-thongtin">
                    <div class="don-thongtin">
                        <p class="label">Mã lớp</p>
                        <p class="label-content" id="class-code">Đang tải...</p>
                    </div>
                    <div class="don-thongtin">
                        <p class="label">Năm học</p>
                        <p class="label-content" id="academic-year">Đang tải...</p>
                    </div>
                </div>
                <div class="frame-thongtin">
                    <div class="don-thongtin">
                        <p class="label">Học phần</p>
                        <p class="label-content" id="course-name">Đang tải...</p>
                    </div>
                    <div class="don-thongtin">
                        <p class="label">Kỳ học</p>
                        <p class="label-content" id="semester">Đang tải...</p>
                    </div>
                </div>
                <div class="frame-thongtin">
                    <div class="don-thongtin">
                        <p class="label">Khoa</p>
                        <p class="label-content" id="faculty">Đang tải...</p>
                    </div>
                    <div class="don-thongtin">
                        <p class="label">Trạng thái</p>
                        <p class="label-content" id="status">Đang tải...</p>
                    </div>
                </div>
                <div class="frame-thongtin">
                    <div class="don-thongtin">
                        <p class="label">Chuyên ngành</p>
                        <p class="label-content" id="major">Đang tải...</p>
                    </div>
                    <div class="don-thongtin">
                        <p class="label">Giảng viên</p>
                        <p class="label-content" id="lecturer">Đang tải...</p>
                    </div>
                </div>
                <div class="frame-thongtin">
                    <div class="don-thongtin">
                        <p class="label">Ca học</p>
                        <p class="label-content" id="time-slot">Đang tải...</p>
                    </div>
                    <div class="don-thongtin">
                        <p class="label">Lịch học</p>
                        <p class="label-content" id="schedule">Đang tải...</p>
                    </div>
                    <div class="don-thongtin">
                        <p class="label">Phòng học</p>
                        <p class="label-content" id="room">Đang tải...</p>
                    </div>
                </div>
                <div class="frame-thongtin">
                    <div class="don-thongtin">
                        <p class="label">Sức chứa</p>
                        <p class="label-content" id="capacity">Đang tải...</p>
                    </div>
                    <div class="don-thongtin">
                        <p class="label">Sỉ số</p>
                        <p class="label-content" id="current-students">Đang tải...</p>
                    </div>
                    <div class="don-thongtin">
                        <p class="label">Sơ đồ điểm</p>
                        <p class="label-content" id="grading-scheme">Đang tải...</p>
                    </div>
                </div>
            </div>
            <div id="bottom">
                <div class="student-box">

                    <div class="box-header">
                        <h3>Danh sách sinh viên (<span id="student-count">0</span>)</h3>
                        <a href="{{ route('admin.lophoc.edit.step2', ['id' => request()->route('id')]) }}" style="text-decoration: none;">
                            <button class="add-btn">
                                <i class="fa-solid fa-plus"></i> Thêm sinh viên
                            </button>
                        </a>
                    </div>

                    <table class="student-list">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>MSSV</th>
                                <th>Họ và tên</th>
                                <th>Khoa/viện</th>
                                <th>Chuyên ngành</th>
                                <th>Tình trạng</th>
                                <th>Trạng thái lớp</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="student-table-body">
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 20px;">
                                    <i class="fa-solid fa-spinner" style="animation: spin 1s linear infinite;"></i> Đang tải dữ liệu...
                                </td>
                            </tr>
                        </tbody>

                    </table>
                    <div class="pagination" id="pagination">
                        <!-- Pagination will be rendered by JavaScript -->
                    </div>


                </div>

            </div>
        </div>
    </div>

    <!-- Javascript -->
    <script src="{{ asset('js/admin/admin.js') }}"></script>
    <script src="{{ asset('js/admin/adminHocphandetail.js') }}"></script>
</body>

</html>