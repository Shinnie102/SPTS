<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- hạn chế đụng vào file overall.css -->
    <link rel="stylesheet" href="{{ asset('css/overall.css') }}">
    <!-- --------------------------------- -->
    <link rel="stylesheet" href="{{ asset('css/admin/adminBuoc2Taolophoc.css') }}">
    <link
        href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400;500;600;700&family=Roboto:wght@100;300;400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/all.min.css') }}">
    <title>Bước 2: Thành viên lớp học</title>
</head>

<body>
    <!-- Header -->
    @include('partials.header_admin')
    <div id="main">
        <!-- Menu -->
        @include('admin.menu_admin')

        <div id="content">
            <!-- Vui lòng điểu chỉnh tiêu đề, không thay đổi tên id có sẵn -->
            <h1 id="tieudechinh">Bước 2: Thành viên lớp học</h1>
            <p id="tieudephu">Chọn giảng viên và sinh viên cho lớp</p>

            <!-- ------------------------------------------------ -->
            <!-- Nội dung riêng của từng trang sẽ được chèn vào đây -->
            <div id="top">
                <div id="buoc1">1</div>
                <div id="process"></div>
                <div id="buoc2">2</div>
            </div>
            <div id="bottom">
                <div id="left">
                    <div id="frame-giangvien">
                        <p class="title">Giảng viên <span>(*)</span></p>
                        <select name="giangvien" id="giangvien">-- Chọn giảng viên --
                            <option value="-- Chọn giảng viên --">-- Chọn giảng viên --</option>
                        </select>
                    </div>

                    <div id="sinhvien">
                        <div id="title-sinhvien">
                            <p class="title">Sinh viên <span>(*)</span></p>
                            <div id="soluong-dachon">
                                <p>Đã chọn: <span id="dachon">2/20</span></p>
                            </div>
                        </div>

                        <div id="search">
                            <input type="text" id="search-sinhvien" placeholder="Tìm kiếm sinh viên...">
                            <i class="fas fa-search"></i>
                        </div>
                        <div id="select-form">
                            <select name="khoa" id="khoa">
                                <option value="Tất cả Khoa/Viên">Tất cả Khoa/Viên</option>
                                <option value="CNTT">CNTT</option>
                                <option value="CNPM">CNPM</option>
                            </select>

                            <select name="chuyennganh" id="chuyennganh">
                                <option value="Tất cả Chuyên ngành">Tất cả Chuyên ngành</option>
                                <option value="KTPM">KTPM</option>
                                <option value="ATTT">ATTT</option>
                            </select>
                        </div>

                        <div class="student-table-wrapper">
                            <table class="student-table">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox"></th>
                                        <th>MSSV</th>
                                        <th>Họ và tên</th>
                                        <th>Khoa/Viện</th>
                                        <th>Chuyên ngành</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><input type="checkbox"></td>
                                        <td>0000000000000</td>
                                        <td>Nguyễn Huỳnh Văn A</td>
                                        <td>Công nghệ thông tin</td>
                                        <td>Công nghệ thông tin</td>
                                    </tr>
                                    <tr>
                                        <td><input type="checkbox"></td>
                                        <td>0000000000000</td>
                                        <td>Nguyễn Huỳnh Văn A</td>
                                        <td>Công nghệ thông tin</td>
                                        <td>Công nghệ thông tin</td>
                                    </tr>
                                    <tr>
                                        <td><input type="checkbox"></td>
                                        <td>0000000000000</td>
                                        <td>Nguyễn Huỳnh Văn A</td>
                                        <td>Công nghệ thông tin</td>
                                        <td>Công nghệ thông tin</td>
                                    </tr>
                                    <tr>
                                        <td><input type="checkbox"></td>
                                        <td>0000000000000</td>
                                        <td>Nguyễn Huỳnh Văn A</td>
                                        <td>Công nghệ thông tin</td>
                                        <td>Công nghệ thông tin</td>
                                    </tr>
                                    <tr>
                                        <td><input type="checkbox"></td>
                                        <td>0000000000000</td>
                                        <td>Nguyễn Huỳnh Văn A</td>
                                        <td>Công nghệ thông tin</td>
                                        <td>Công nghệ thông tin</td>
                                    </tr>
                                    <tr>
                                        <td><input type="checkbox"></td>
                                        <td>0000000000000</td>
                                        <td>Nguyễn Huỳnh Văn A</td>
                                        <td>Công nghệ thông tin</td>
                                        <td>Công nghệ thông tin</td>
                                    </tr>
                                    <tr>
                                        <td><input type="checkbox"></td>
                                        <td>0000000000000</td>
                                        <td>Nguyễn Huỳnh Văn A</td>
                                        <td>Công nghệ thông tin</td>
                                        <td>Công nghệ thông tin</td>
                                    </tr>
                                    <tr>
                                        <td><input type="checkbox"></td>
                                        <td>0000000000000</td>
                                        <td>Nguyễn Huỳnh Văn A</td>
                                        <td>Công nghệ thông tin</td>
                                        <td>Công nghệ thông tin</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="pagination">
                            <button class="page-btn disabled">‹</button>

                            <button class="page-btn active">1</button>
                            <button class="page-btn">2</button>
                            <button class="page-btn">3</button>
                            <button class="page-btn">4</button>

                            <button class="page-btn">›</button>
                        </div>

                    </div>
                </div>
                <div id="right">
                    <p class="title">Bối cảnh học thuật</p>

                    <p class="ten-hienthi">Năm học</p>
                    <p class="ketqua">Chưa chọn</p>

                    <p class="ten-hienthi">Kỳ học</p>
                    <p class="ketqua">Chưa chọn</p>

                    <div class="chan"></div>

                    <p class="ten-hienthi">Khoa</p>
                    <p class="ketqua">Chưa chọn</p>

                    <p class="ten-hienthi">Chuyên ngành</p>
                    <p class="ketqua">Chưa chọn</p>

                    <p class="ten-hienthi">Học phần</p>
                    <p class="ketqua">Chưa chọn</p>

                    <div class="chan"></div>

                    <p class="ten-hienthi">Mã lớp</p>
                    <p class="ketqua">Chưa nhập</p>

                    <p class="ten-hienthi">Ca học</p>
                    <p class="ketqua">Chưa chọn</p>

                    <p class="ten-hienthi">Lịch học</p>
                    <p class="ketqua">Chưa chọn</p>

                    <p class="ten-hienthi">Phòng học</p>
                    <p class="ketqua">Chưa chọn</p>

                    <p class="ten-hienthi">Sức chứa</p>
                    <p class="ketqua">Chưa nhập</p>

                </div>
            </div>

            <div id="thaotac">
                <button class="btn" id="quaylai">Quay lại</button>
                <button class="btn" id="hoantat">Hoàn tất & tạo lớp</button>
            </div>
        </div>
    </div>

    <!-- Javascript -->
    <script src="{{ asset('js/admin/admin.js') }}"></script>
    <script src="{{ asset('js/admin/adminBuoc2Taolophoc.js') }}"></script>
</body>

</html>