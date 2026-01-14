<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
    <div class="header"></div>
    <div id="main">
        <!-- Menu -->
        <div class="menu_admin"></div>

        <div id="content">
            <!-- Vui lòng điểu chỉnh tiêu đề, không thay đổi tên id có sẵn -->
            <h1 id="tieudechinh">Phân công Lớp học</h1>
            <p id="tieudephu">Quản lý Lớp học phần và Phân công Giảng viên</p>

            <!-- ------------------------------------------------ -->
            <!-- Nội dung riêng của từng trang sẽ được chèn vào đây -->
            <div id="top">
                <div id="find">
                    <input type="text" name="timkiem" id="timkiem" placeholder="Nhập tên hoặc mã lớp học.">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <div id="add-hocphan">
                    <a href="!"><i class="fa-solid fa-plus" style="color: white;"></i> <span style="color:white">Thêm
                            Lớp học phần</span></a>
                </div>
            </div>
            <div id="mid">
                <div class="filter-bar">

                    <!-- KHOA -->
                    <div class="fake-select">
                        <div class="selected">Tất cả khoa<i class="fa-solid fa-angle-down"></i></div>
                        <div class="options">
                            <div class="option" data-value="Tất cả khoa">Tất cả khoa</div>
                            <div class="option" data-value="Công nghệ thông tin">Công nghệ thông tin</div>
                            <div class="option" data-value="Kinh tế">Kinh tế</div>
                            <div class="option" data-value="Quản trị kinh doanh">Quản trị kinh doanh</div>
                            <div class="option" data-value="Ngoại ngữ">Ngoại ngữ</div>
                        </div>
                        <input type="hidden" name="khoa" value="Tất cả khoa">
                    </div>

                    <!-- CHUYÊN NGÀNH -->
                    <div class="fake-select">
                        <div class="selected">Tất cả chuyên ngành<i class="fa-solid fa-angle-down"></i></div>
                        <div class="options">
                            <div class="option" data-value="Tất cả chuyên ngành">Tất cả chuyên ngành</div>
                            <div class="option" data-value="Mạng máy tính và truyền thông dữ liệu">Mạng máy tính và
                                truyền thông dữ liệu</div>
                            <div class="option" data-value="Khoa học máy tính">Khoa học máy tính</div>
                            <div class="option" data-value="Hệ thống thông tin">Hệ thống thông tin</div>
                            <div class="option" data-value="An toàn thông tin">An toàn thông tin</div>
                        </div>
                        <input type="hidden" name="chuyennganh" value="Tất cả chuyên ngành">
                    </div>

                    <!-- HỌC KỲ -->
                    <div class="fake-select">
                        <div class="selected">Tất cả học kỳ<i class="fa-solid fa-angle-down"></i></div>
                        <div class="options">
                            <div class="option" data-value="Tất cả học kỳ">Tất cả học kỳ</div>
                            <div class="option" data-value="Học kỳ 1">Kỳ 1 2024-2025</div>
                            <div class="option" data-value="Học kỳ 2">Kỳ 2 2024-2025</div>
                            <div class="option" data-value="Học kỳ hè">Kỳ hè 2024-2025</div>
                        </div>
                        <input type="hidden" name="hocky" value="Tất cả học kỳ">
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
                        <tbody>
                            <tr>
                                <td>CS101.01</td>
                                <td>Lập trình cơ bản</td>
                                <td>1</td>
                                <td>48/50</td>
                                <td>Nguyễn Văn A</td>
                                <td><span class="badge active">Hoạt động</span></td>
                                <td class="action">
                                    <i class="fa-solid fa-pen-to-square edit"></i>
                                    <i class="fa-solid fa-trash delete"></i>
                                </td>
                            </tr>

                            <tr>
                                <td>CS101</td>
                                <td>Lập trình mạng</td>
                                <td>1</td>
                                <td>48/50</td>
                                <td>Nguyễn Văn A</td>
                                <td><span class="badge active">Hoạt động</span></td>
                                <td class="action">
                                    <i class="fa-solid fa-pen-to-square edit"></i>
                                    <i class="fa-solid fa-trash delete"></i>
                                </td>
                            </tr>

                            <tr>
                                <td>CS101</td>
                                <td>Lập trình mạng</td>
                                <td>1</td>
                                <td>48/50</td>
                                <td>Nguyễn Văn A</td>
                                <td><span class="badge pending">Tạm ngưng</span></td>
                                <td class="action">
                                    <i class="fa-solid fa-pen-to-square edit"></i>
                                    <i class="fa-solid fa-trash delete"></i>
                                </td>
                            </tr>

                            <tr>
                                <td>CS101</td>
                                <td>Lập trình mạng</td>
                                <td>1</td>
                                <td>48/50</td>
                                <td>Nguyễn Văn A</td>
                                <td><span class="badge closed">Đóng</span></td>
                                <td class="action">
                                    <i class="fa-solid fa-pen-to-square edit"></i>
                                    <i class="fa-solid fa-trash delete"></i>
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