<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- hạn chế đụng vào file overall.css -->
    <link rel="stylesheet" href="{{ asset('css/overall.css') }}">
    <!-- --------------------------------- -->
    <link rel="stylesheet" href="{{ asset('css/admin/adminQuytac.css') }}">
    <link
        href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400;500;600;700&family=Roboto:wght@100;300;400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/all.min.css') }}">
    <title>Quy tắc đánh giá</title>
</head>

<body>
    <!-- Header -->
    @include('partials.header_admin')
    <div id="main">
        <!-- Menu -->
        @include('admin.menu_admin')

        <div id="content">
            <!-- Vui lòng điểu chỉnh tiêu đề, không thay đổi tên id có sẵn -->
            <h1 id="tieudechinh">Quy tắc Đánh giá</h1>
            <p id="tieudephu">Quản lý Sơ đồ điểm chuẩn và Quy tắc Học vụ</p>

            <!-- ------------------------------------------------ -->
            <!-- Nội dung riêng của từng trang sẽ được chèn vào đây -->
            <div id="frame-quytac">
                <p id="quytachocvu">Quy tắc Học vụ</p>
                <!-- Dữ liệu sẽ được load bằng JavaScript -->
            </div>

            <div id="frame-sododiem">
                <div id="title-sododiem">
                    <p>Sơ đồ điểm </p>
                    <button id="add-sododiem"><i class="fa-solid fa-plus"></i> Thêm sơ đồ điểm</button>
                </div>
                <!-- Dữ liệu sẽ được load bằng JavaScript -->
            </div>

            
            <div class="frame-module">
                <div class="module themsododiem">
                    <div class="title-sododiem">
                        <p>Thêm sơ đồ điểm</p>
                        <i class="fa-solid fa-xmark"></i>
                    </div>
                    <p class="infor">Tên sơ đồ <span>(*)</span></p>
                    <input type="text" placeholder="Nhập tên sơ đồ">
                    <p class="infor">Mã sơ đồ <span>(*)</span></p>
                    <input type="text" placeholder="Nhập mã sơ đồ">
                    <p class="infor">Thanh phần điểm <span id="sophantram">(100/100)</span></p>
                    <div class="themthanhphandiem">
                        <input type="text" placeholder="Tên thành phần điểm" class="tenthanhphandiem">
                        <input type="text" placeholder="0%" class="phantramthanhphan">
                        <button id="xoathanhphandiem">Xoá</button>
                    </div>
                    <button id="themthanhphan">+ Thêm thành phần điểm</button>
                    <div class="thaotac">
                        <button class="btn them">Thêm</button>
                        <button class="btn huy">Hủy</button>
                    </div>
                </div>
                <div class="module suasododiem">
                    <div class="title-sododiem">
                        <p>Sửa sơ đồ điểm</p>
                        <i class="fa-solid fa-xmark"></i>
                    </div>
                    <p class="infor">Tên sơ đồ <span>(*)</span></p>
                    <input type="text" placeholder="Nhập tên sơ đồ" value="Sơ đồ tiêu chuẩn-kỹ thuật">
                    <p class="infor">Mã sơ đồ <span>(*)</span></p>
                    <input type="text" placeholder="Nhập mã sơ đồ" value="STD-HUM">
                    <p class="infor">Thanh phần điểm <span id="sophantram">100/100</span></p>
                    <div class="themthanhphandiem">
                        <input type="text" placeholder="Tên thành phần điểm" class="tenthanhphandiem" value="Tham gia lớp">
                        <input type="text" placeholder="0%" class="phantramthanhphan" value="20%">
                        <button id="xoathanhphandiem">Xoá</button>
                    </div>
                    <div class="themthanhphandiem">
                        <input type="text" placeholder="Tên thành phần điểm" class="tenthanhphandiem" value="Báo cáo nhóm">
                        <input type="text" placeholder="0%" class="phantramthanhphan" value="40%">
                        <button id="xoathanhphandiem">Xoá</button>
                    </div>
                    <div class="themthanhphandiem">
                        <input type="text" placeholder="Tên thành phần điểm" class="tenthanhphandiem" value="Thi cuối kì">
                        <input type="text" placeholder="0%" class="phantramthanhphan" value="40%">
                        <button id="xoathanhphandiem">Xoá</button>
                    </div>
                    <button id="themthanhphan">+ Thêm thành phần điểm</button>
                    <div class="thaotac">
                        <button class="btn chinhsua">Chỉnh sửa</button>
                        <button class="btn huy">Hủy</button>
                    </div>
                </div>
            </div>

            <div class="lock">
                <p id="content-lock">Sơ đò đang được sử dụng. Không thể chỉnh sửa</p>
            </div>
        </div>
    </div>

    <!-- Javascript -->
    <script src="{{ asset('js/admin/admin.js') }}"></script>
    <script src="{{ asset('js/admin/adminQuytac.js') }}"></script>
</body>

</html>