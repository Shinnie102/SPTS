<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- hạn chế đụng vào file overall.css -->
    <link rel="stylesheet" href="{{ asset('css/overall.css') }}">
    <!-- --------------------------------- -->
    <link rel="stylesheet" href="{{ asset('css/admin/adminThoigian.css') }}">
    <link
        href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400;500;600;700&family=Roboto:wght@100;300;400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/all.min.css') }}">
    <title>Thời gian học vụ</title>
</head>

<body>
    <!-- Header -->
    @include('partials.header_admin')
    <div id="main">
        <!-- Menu -->
        @include('admin.menu_admin')

        <div id="content">
            <!-- Vui lòng điều chỉnh tiêu đề, không thay đổi tên id có sẵn -->
            <div id="head">
                <div class="left">
                    <h1 id="tieudechinh">Thời gian Học vụ</h1>
                    <p id="tieudephu">Quản lý Năm học và Kỳ học theo cấu trúc phân cấp</p>
                </div>
                <button id="add-namhoc"><i class="fa-solid fa-plus"></i> Thêm năm học</button>
            </div>

            <!-- ------------------------------------------------ -->
            <!-- Nội dung riêng của từng trang sẽ được chèn vào đây -->
            <div id="frame-quytac">
                <p id="quytac">Quy tắc</p>
                <div id="noidung-quytac">Không thể xóa năm học nếu đã có kỳ học hoạt động. Không thể xóa kỳ học nếu đã
                    có lớp mở.</div>
            </div>
            
            <!-- Container để render danh sách năm học -->
            <div class="frame-noidung" id="academic-years-container">
                <!-- Loading indicator -->
                <div class="loading-indicator" style="text-align: center; padding: 2rem;">
                    <i class="fa-solid fa-spinner fa-spin" style="font-size: 2rem; color: #0088F0;"></i>
                    <p style="margin-top: 1rem; color: #615F5F;">Đang tải dữ liệu...</p>
                </div>
            </div>

            <!-- Modal thêm năm học -->
            <div class="frame-noi themnamhoc">
                <div class="title-noi">
                    <p>Thêm năm học</p>
                    <i class="fa-solid fa-x close-modal"></i>
                </div>
                <p class="tieude">Tên năm học<span> (*)</span></p>
                <input type="text" class="input-noi" id="year-code-input" placeholder="Nhập tên năm học (VD: 2024-2025)">
                <p class="tieude">Ngày bắt đầu<span> (*)</span></p>
                <input type="date" name="ngaybatdau" id="year-start-date" class="input-noi">
                <p class="tieude">Ngày kết thúc<span> (*)</span></p>
                <input type="date" name="ngayketthuc" id="year-end-date" class="input-noi">
                <div class="thaotac">
                    <button class="btn them" id="btn-add-year">Thêm</button>
                    <button class="btn huy close-modal">Hủy</button>
                </div>
            </div>

            <!-- Modal thêm học kỳ -->
            <div class="frame-noi themkihoc">
                <div class="title-noi">
                    <p>Thêm học kỳ vào <span id="target-year-name"></span></p>
                    <i class="fa-solid fa-x close-modal"></i>
                </div>
                <input type="hidden" id="target-academic-year-id">
                <p class="tieude">Tên kỳ<span> (*)</span></p>
                <input type="text" class="input-noi" id="semester-code-input" placeholder="Nhập tên kỳ học (VD: Học kỳ 1)">
                <p class="tieude">Ngày bắt đầu<span> (*)</span></p>
                <input type="date" name="ngaybatdau" id="semester-start-date" class="input-noi">
                <p class="tieude">Ngày kết thúc<span> (*)</span></p>
                <input type="date" name="ngayketthuc" id="semester-end-date" class="input-noi">
                <div class="thaotac">
                    <button class="btn them" id="btn-add-semester">Thêm</button>
                    <button class="btn huy close-modal">Hủy</button>
                </div>
            </div>

            <!-- Modal sửa học kỳ -->
            <div class="frame-noi editkyhoc">
                <div class="title-noi">
                    <p>Chỉnh sửa học kỳ</p>
                    <i class="fa-solid fa-x close-modal"></i>
                </div>
                <input type="hidden" id="edit-semester-id">
                <p class="tieude">Tên kỳ<span> (*)</span></p>
                <input type="text" class="input-noi" id="edit-semester-code" placeholder="Nhập tên kỳ học">
                <p class="tieude">Ngày bắt đầu<span> (*)</span></p>
                <input type="date" name="ngaybatdau" id="edit-semester-start-date" class="input-noi">
                <p class="tieude">Ngày kết thúc<span> (*)</span></p>
                <input type="date" name="ngayketthuc" id="edit-semester-end-date" class="input-noi">
                <div class="thaotac">
                    <button class="btn chinhsua" id="btn-update-semester">Chỉnh sửa</button>
                    <button class="btn huy close-modal">Hủy</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Javascript -->
    <script src="{{ asset('js/admin/admin.js') }}"></script>
    <script src="{{ asset('js/admin/adminThoigian.js') }}"></script>
</body>

</html>