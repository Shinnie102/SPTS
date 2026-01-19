<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="route-admin-lophoc-api-index" content="{{ route('admin.lophoc.api.index') }}">
    <meta name="route-admin-lophoc-api-filters" content="{{ route('admin.lophoc.api.filters') }}">
    <meta name="route-admin-lophoc-api-majors" content="{{ route('admin.lophoc.api.majors') }}">
    <meta name="route-admin-lophoc-detail-prefix" content="{{ url('/admin/lop-hoc') }}">
    <!-- hạn chế đụng vào file overall.css -->
    <link rel="stylesheet" href="{{ asset('css/overall.css') }}">
    <!-- --------------------------------- -->
    <link rel="stylesheet" href="{{ asset('css/admin/adminLophoc.css') }}">
    <link
        href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400;500;600;700&family=Roboto:wght@100;300;400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/all.min.css') }}">
    <title>Phân công lớp học</title>
</head>

<body>
    <!-- Header -->
    @include('partials.header_admin')
    <div id="main">
        <!-- Menu -->
        @include('admin.menu_admin')

        <div id="content">
            <!-- Vui lòng điểu chỉnh tiêu đề, không thay đổi tên id có sẵn -->
            <h1 id="tieudechinh">Phân công Lớp học</h1>
            <p id="tieudephu">Quản lý Lớp học phần và Phân công Giảng viên</p>
            <div id="toast" style="display:none; position:fixed; top:20px; right:20px; padding:12px 16px; border-radius:6px; color:#fff; z-index:9999; box-shadow:0 4px 12px rgba(0,0,0,0.15); font-weight:500;"></div>

            <!-- ------------------------------------------------ -->
            <!-- Nội dung riêng của từng trang sẽ được chèn vào đây -->
            <div id="top">
                <div id="find">
                    <input type="text" name="timkiem" id="timkiem" placeholder="Nhập tên hoặc mã lớp học.">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <div id="add-hocphan">
                    <a href="{{ route('admin.lophoc.create.step1') }}"><i class="fa-solid fa-plus" style="color: white;"></i> <span style="color:white">Thêm
                            Lớp học phần</span></a>
                </div>
            </div>
            <div id="mid">
                <div class="filter-bar">

                    <!-- KHOA -->
                    <div class="fake-select">
                        <div class="selected">Tất cả khoa<i class="fa-solid fa-angle-down"></i></div>
                        <div class="options">
                            <div class="option" data-value="">Tất cả khoa</div>
                        </div>
                        <input type="hidden" name="khoa" id="khoa-filter" value="">
                    </div>

                    <!-- CHUYÊN NGÀNH -->
                    <div class="fake-select">
                        <div class="selected">Tất cả chuyên ngành<i class="fa-solid fa-angle-down"></i></div>
                        <div class="options">
                            <div class="option" data-value="">Tất cả chuyên ngành</div>
                        </div>
                        <input type="hidden" name="chuyennganh" id="major-filter" value="">
                    </div>

                    <!-- HỌC KỲ -->
                    <div class="fake-select">
                        <div class="selected">Tất cả học kỳ<i class="fa-solid fa-angle-down"></i></div>
                        <div class="options">
                            <div class="option" data-value="">Tất cả học kỳ</div>
                        </div>
                        <input type="hidden" name="hocky" id="semester-filter" value="">
                    </div>

                </div>


                <div class="table-wrapper">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Mã lớp</th>
                                <th>Tên môn học</th>
                                <th>Kì học</th>
                                <th>Sức chứa</th>
                                <th>Giảng viên</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="class-table-body">
                            <!-- Dữ liệu sẽ được load bằng JavaScript -->
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 20px;">
                                    <i class="fa-solid fa-spinner" style="animation: spin 1s linear infinite;"></i> Đang tải dữ liệu...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>

    <!-- Javascript -->
    <script src="{{ asset('js/admin/admin.js') }}"></script>
    <script src="{{ asset('js/admin/adminLophoc.js') }}"></script>
</body>

</html>