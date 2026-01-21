<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="route-admin-lophoc-api-create-options" content="{{ route('admin.lophoc.api.create.options') }}">
    <meta name="route-admin-lophoc-api-create-step1" content="{{ route('admin.lophoc.api.create.step1') }}"><meta name="route-admin-lophoc-api-semesters-by-year" content="{{ route('admin.lophoc.api.semestersByYear') }}">
<meta name="route-admin-lophoc-api-courses-by-major" content="{{ route('admin.lophoc.api.coursesByMajor') }}">
<meta name="route-admin-lophoc-api-majors" content="{{ route('admin.lophoc.api.majors') }}">    <meta name="route-admin-lophoc-api-create-step1-get" content="{{ route('admin.lophoc.api.create.step1.get') }}">
    <!-- hạn chế đụng vào file overall.css -->
    <link rel="stylesheet" href="{{ asset('css/overall.css') }}">
    <!-- --------------------------------- -->
    <link rel="stylesheet" href="{{ asset('css/admin/adminBuoc1Taolophoc.css') }}">
    <link
        href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400;500;600;700&family=Roboto:wght@100;300;400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/all.min.css') }}">
    <title>Bước1: Thông tin lớp học</title>
</head>

<body>
    <!-- Header -->
    @include('partials.header_admin')
    <div id="main">
        <!-- Menu -->
        @include('admin.menu_admin')

        <div id="content">
            <!-- Vui lòng điểu chỉnh tiêu đề, không thay đổi tên id có sẵn -->
            <h1 id="tieudechinh">Bước 1: Thông tin Lớp học</h1>
            <p id="tieudephu">Định nghĩa bối cảnh học thuật của lớp học phần</p>

            <!-- ------------------------------------------------ -->
            <!-- Nội dung riêng của từng trang sẽ được chèn vào đây -->
            <div id="top">
                <div id="buoc1">1</div>
                <div id="process"></div>
                <div id="buoc2">2</div>
            </div>
            <div id="bottom">
                <div id="left">
                    <p class="ten">Năm học <span>(*)</span></p>
                    <select name="namhoc" id="namhoc">-- Chọn năm học --
                        <option value="-- Chọn năm học --">-- Chọn năm học --</option>
                    </select>

                    <p class="ten">Kỳ học <span>(*)</span></p>
                    <select name="kyhoc" id="kyhoc">-- Chọn Kỳ học --
                        <option value="-- Chọn Kỳ học --">-- Chọn Kỳ học --</option>
                    </select>
                    <!-- ------------------------------------------------ -->
                    <div class="chan"></div>

                    <p class="title">Bối cảnh học thuật</p>

                    <p class="ten">Khoa/Viện <span>(*)</span></p>
                    <select name="khoa" id="khoa">-- Chọn khoa/viện --
                        <option value="-- Chọn khoa/viện --">-- Chọn khoa/viện --</option>
                    </select>

                    <p class="ten">Chuyên ngành <span>(*)</span></p>
                    <select name="chuyennganh" id="chuyennganh">-- Chọn chuyên ngành --
                        <option value="-- Chọn chuyên ngành --">-- Chọn chuyên ngành --</option>
                    </select>

                    <p class="ten">Học phần <span>(*)</span></p>
                    <select name="hocphan" id="hocphan">-- Chọn học phần --
                        <option value="-- Chọn học phần --">-- Chọn học phần --</option>
                    </select>
                    <!-- ------------------------------------------------ -->
                    <div class="chan"></div>

                    <p class="title">Tổ chức lớp</p>

                    <div id="tren">
                        <div id="trai">
                            <p class="ten">Mã lớp</p>
                            <input type="text" placeholder="Nhập mã lớp">
                        </div>
                        <div id="phai">
                            <p class="ten">Ca học</p>
                            <select name="cahoc" id="cahoc">-- Chọn ca học --
                                <option value="-- Chọn ca học --">-- Chọn ca học --</option>
                            </select>
                        </div>
                    </div>

                    <div id="duoi">
                        <div id="trai">
                            <p class="ten">Lịch học <span>(*)</span></p>
                            <div id="lichhoc" style="display: grid; grid-template-columns: 1fr 1fr; gap: 5px; padding: 5px; background: white;"></div>
                        </div>
                        <div id="phai">
                            <p class="ten">Phòng học</p>
                            <select name="phonghoc" id="phonghoc">-- Chọn phòng học --
                                <option value="-- Chọn phòng học --">-- Chọn phòng học --</option>
                            </select>
                        </div>
                    </div>

                    <div id="final">
                        <p class="ten">Sức chứa <span>(*)</span></p>
                        <input type="number" name="suchua" id="suchua" placeholder="Số lượng sinh viên tối đa">
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
                <button class="btn" id="huy">Hủy</button>
                <button class="btn" id="tieptuc">Tiếp tục</button>
            </div>
        </div>
    </div>

    <!-- Javascript -->
    <script src="{{ asset('js/admin/admin.js') }}"></script>
    <script src="{{ asset('js/admin/adminBuoc1Taolophoc.js') }}"></script>
</body>

</html>