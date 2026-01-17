<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- hạn chế đụng vào file overall.css -->
    <link rel="stylesheet" href="{{ asset('css/overall.css') }}">
    <!-- --------------------------------- -->
    <link rel="stylesheet" href="{{ asset('css/admin/adminhocthuat.css') }}">
    <link
        href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400;500;600;700&family=Roboto:wght@100;300;400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/all.min.css') }}">
    <title>Cấu trúc học thuật</title>
</head>

<body>
    <!-- Header -->
    @include('partials.header_admin')
    <div id="main">
        <!-- Menu -->
        @include('admin.menu_admin')

        <div id="content">
            <!-- Vui lòng điểu chỉnh tiêu đề, không thay đổi tên id có sẵn -->
            <h1 id="tieudechinh">Cấu trúc Học thuật</h1>
            <p id="tieudephu">Quản lý Khoa, Chuyên ngành, Học phần</p>

            <!-- ------------------------------------------------ -->
            <!-- Nội dung riêng của từng trang sẽ được chèn vào đây -->

            <div id="top-content">
                <div id="frame-menu">
                    <div class="menu-in active-nav">Khoa/Viện</div>
                    <div class="menu-in">Học phần</div>
                </div>

                <div id="find">
                    <input type="text" id="tim-kiem" placeholder="Nhập tên hoặc mã">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
            </div>

            <div id="bottom-content">
                <!-- Khoa/Viện -->
                <div class="frame-KhoaVien">
                    <button id="add-khoavien" class="add">
                        <i class="fa-solid fa-plus"></i>
                        Thêm Khoa/Viện
                    </button>
                    <div class="khung-khoanganh">
                        <div class="khung-khoavien">
                            <div class="left">
                                <div class="khoa-vien">
                                    <i class="fa-solid fa-angle-right"></i>
                                    <p class="ten-khoa">Công nghệ thông tin</p>
                                </div>

                                <p class="frame-ma">Mã: <span class="ma-khoa">CN</span></p>
                            </div>

                            <div class="right">
                                <div class="frame-loai">
                                    <p class="loai">Khoa</p>
                                </div>

                                <div class="frame-chuyennganh">
                                    <p class="chuyennganh">Chuyên ngành: <span class="soluong">12</span></p>
                                </div>

                                <div class="frame-status">
                                    <p class="status" class="hoatdong">Hoạt động</p>
                                </div>

                                <i class="fa-solid fa-trash"></i>
                            </div>
                        </div>

                        <div class="khung-chuyennganh">
                            <div class="title-chuyennganh">
                                <p>Chuyên ngành:</p>
                                <button id="them-chuyennganh">
                                    <i class="fa-solid fa-plus"></i>
                                    Thêm
                                </button>
                            </div>

                            <div class="khung-cacnganh">
                                <div class="nghanh">
                                    <div class="left">
                                        <p class="tennghanh">Hệ thống thông tin</p>
                                        <p class="manganh">Mã <span class="ma">00001</span></p>
                                    </div>
                                    <div class="right">
                                        <i class="fa-solid fa-trash"></i>
                                    </div>
                                </div>
                                <div class="nghanh">
                                    <div class="left">
                                        <p class="tennghanh">Hệ thống thông tin</p>
                                        <p class="manganh">Mã <span class="ma">00001</span></p>
                                    </div>
                                    <div class="right">
                                        <i class="fa-solid fa-trash"></i>
                                    </div>
                                </div>
                                <div class="nghanh">
                                    <div class="left">
                                        <p class="tennghanh">Hệ thống thông tin</p>
                                        <p class="manganh">Mã <span class="ma">00001</span></p>
                                    </div>
                                    <div class="right">
                                        <i class="fa-solid fa-trash"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- ------------------------------------------------- -->
                    <div class="khung-khoanganh">
                        <div class="khung-khoavien">
                            <div class="left">
                                <div class="khoa-vien">
                                    <i class="fa-solid fa-angle-right"></i>
                                    <p class="ten-khoa">Công nghệ thông tin</p>
                                </div>

                                <p class="frame-ma">Mã: <span class="ma-khoa">CN</span></p>
                            </div>

                            <div class="right">
                                <div class="frame-loai">
                                    <p class="loai">Khoa</p>
                                </div>

                                <div class="frame-chuyennganh">
                                    <p class="chuyennganh">Chuyên ngành: <span class="soluong">12</span></p>
                                </div>

                                <div class="frame-status">
                                    <p class="status" class="hoatdong">Hoạt động</p>
                                </div>

                                <i class="fa-solid fa-trash"></i>
                            </div>
                        </div>

                        <div class="khung-chuyennganh">
                            <div class="title-chuyennganh">
                                <p>Chuyên ngành:</p>
                                <button id="them-chuyennganh">
                                    <i class="fa-solid fa-plus"></i>
                                    Thêm
                                </button>
                            </div>

                            <div class="khung-cacnganh">
                                <div class="nghanh">
                                    <div class="left">
                                        <p class="tennghanh">Hệ thống thông tin</p>
                                        <p class="manganh">Mã <span class="ma">00001</span></p>
                                    </div>
                                    <div class="right">
                                        <i class="fa-solid fa-trash"></i>
                                    </div>
                                </div>
                                <div class="nghanh">
                                    <div class="left">
                                        <p class="tennghanh">Hệ thống thông tin</p>
                                        <p class="manganh">Mã <span class="ma">00001</span></p>
                                    </div>
                                    <div class="right">
                                        <i class="fa-solid fa-trash"></i>
                                    </div>
                                </div>
                                <div class="nghanh">
                                    <div class="left">
                                        <p class="tennghanh">Hệ thống thông tin</p>
                                        <p class="manganh">Mã <span class="ma">00001</span></p>
                                    </div>
                                    <div class="right">
                                        <i class="fa-solid fa-trash"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Học phần  -->
                <div id="frame-hocphan">
                    <div id="fillter">
                        <div class="fake-select" data-name="khoa">
                            <div class="selected">
                                Tất cả các khoa
                                <i class="fa-solid fa-angle-down"></i>
                            </div>

                            <div class="options">
                                <div class="option" data-value="ALL">Tất cả các khoa</div>
                                <div class="option" data-value="CNTT">Công nghệ thông tin</div>
                            </div>
                            <input type="hidden" name="khoa" value="ALL">
                        </div>

                        <!-- CHUYÊN NGÀNH -->
                        <div class="fake-select" data-name="chuyen-nganh">
                            <div class="selected">
                                Tất cả các chuyên ngành
                                <i class="fa-solid fa-angle-down"></i>
                            </div>
                            <div class="options">
                                <div class="option" data-value="ALL">Tất cả các chuyên ngành</div>
                                <div class="option" data-value="HTTT">Hệ thống thông tin</div>
                            </div>
                            <input type="hidden" name="chuyen-nganh" value="ALL">
                        </div>


                        <button id="add-hocphan" class="add">
                            <i class="fa-solid fa-plus"></i>
                            Thêm học phần
                        </button>
                    </div>

                    <div class="table-container">
                        <table class="course-table">
                            <thead>
                                <tr>
                                    <th>Mã học phần</th>
                                    <th>Tên học phần</th>
                                    <th>Tín chỉ</th>
                                    <th>Khoa/Viện</th>
                                    <th>Chuyên ngành</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>LTM101</td>
                                    <td>Lập trình mạng</td>
                                    <td>3</td>
                                    <td>Công nghệ thông tin</td>
                                    <td>Nhúng</td>
                                    <td class="actions">
                                        <button class="edit">
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </button>
                                        <button class="delete">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>LTM101</td>
                                    <td>Lập trình mạng</td>
                                    <td>3</td>
                                    <td>Công nghệ thông tin</td>
                                    <td>Nhúng</td>
                                    <td class="actions">
                                        <button class="edit">
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </button>
                                        <button class="delete">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>LTM101</td>
                                    <td>Lập trình mạng</td>
                                    <td>3</td>
                                    <td>Công nghệ thông tin</td>
                                    <td>Nhúng</td>
                                    <td class="actions">
                                        <button class="edit">
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </button>
                                        <button class="delete">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>LTM101</td>
                                    <td>Lập trình mạng</td>
                                    <td>3</td>
                                    <td>Công nghệ thông tin</td>
                                    <td>Nhúng</td>
                                    <td class="actions">
                                        <button class="edit">
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </button>
                                        <button class="delete">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="pagination-container">
                        <div class="pagination-info">
                            <span id="pagination-info-text"></span>
                        </div>
                        <div class="pagination-controls" id="pagination-controls">
                            <!-- Pagination buttons will be rendered here -->
                        </div>
                    </div>
                </div>
            </div>
            <!-- ------------------------------------------------ -->
            <div class="modal-overlay" id="modalOverlay">

                <div class="frame-new tao-khoa">
                    <div class="title-new">
                        <p class="title">Thêm Khoa/Viện mới</p>
                        <i class="fa-solid fa-xmark"></i>
                    </div>
                    <p class="infor">Tên Khoa/Viện <span>(*)</span></p>
                    <input type="text" id="faculty-name-input" placeholder="Nhập tên Khoa/Viện">
                    <p class="infor">Mã Khoa/Viện <span>(*)</span></p>
                    <input type="text" id="faculty-code-input" placeholder="Nhập mã Khoa/Viện">
                    <div class="thaotac">
                        <button class="them" id="submit-faculty-btn">Thêm</button>
                        <button class="Huy">Hủy</button>
                    </div>
                </div>

                <div class="frame-new them-nganh">
                    <div class="title-new">
                        <p class="title">Thêm chuyên ngành</p>
                        <i class="fa-solid fa-xmark"></i>
                    </div>
                    <input type="hidden" id="major-faculty-id">
                    <p class="infor">Tên chuyên ngành <span>(*)</span></p>
                    <input type="text" id="major-name-input" placeholder="Nhập tên chuyên ngành">
                    <p class="infor">Mã chuyên ngành <span>(*)</span></p>
                    <input type="text" id="major-code-input" placeholder="Nhập mã chuyên ngành">
                    <div class="thaotac">
                        <button class="them">Thêm</button>
                        <button class="Huy">Hủy</button>
                    </div>
                </div>

                <div class="frame-new themhocphan">
                    <div class="title-new">
                        <p class="title">Thêm học phần mới</p>
                        <i class="fa-solid fa-xmark"></i>
                    </div>
                    <p class="infor">Mã học phần <span>(*)</span></p>
                    <input type="text" id="course-code-input" placeholder="Nhập mã học phần (VD: CS101)">
                    <div id="course-code-validation" style="margin-top: 5px; font-size: 13px;"></div>

                    <p class="infor">Tên học phần <span>(*)</span></p>
                    <input type="text" id="course-name-input" placeholder="Nhập tên học phần">

                    <p class="infor">Tín chỉ <span>(*)</span></p>
                    <input type="number" id="course-credit-input" placeholder="Nhập tín chỉ (1-6)" min="1" max="6">

                    <p class="infor">Khoa/Viện <span>(*)</span></p>
                    <select id="course-faculty-select">
                        <option value="">-- Chọn Khoa/Viện --</option>
                    </select>

                    <p class="infor">Chuyên ngành <span>(*)</span></p>
                    <select id="course-major-select">
                        <option value="">-- Chọn Khoa/Viện trước --</option>
                    </select>

                    <p class="infor">Cấu trúc điểm</p>
                    <select id="course-grading-scheme-select">
                        <option value="">-- Chọn cấu trúc điểm --</option>
                    </select>

                    <div class="thaotac">
                        <button class="them" id="submit-course-btn">Thêm</button>
                        <button class="Huy">Hủy</button>
                    </div>
                </div>

                <div class="frame-new chitiethocphan">
                    <div class="title-new">
                        <p class="title">Chi tiết học phần</p>
                        <i class="fa-solid fa-xmark"></i>
                    </div>
                    <input type="hidden" id="edit-course-id">
                    
                    <p class="infor">Mã học phần <span>(*)</span></p>
                    <input type="text" id="edit-course-code" disabled style="background-color: #f0f0f0; cursor: not-allowed;">

                    <p class="infor">Tên học phần <span>(*)</span></p>
                    <input type="text" id="edit-course-name" placeholder="Nhập tên học phần">

                    <p class="infor">Tín chỉ <span>(*)</span></p>
                    <input type="number" id="edit-course-credit" placeholder="Nhập tín chỉ (1-6)" min="1" max="6">

                    <p class="infor">Khoa/Viện <span>(*)</span></p>
                    <select id="edit-course-faculty-select">
                        <option value="">-- Chọn Khoa/Viện --</option>
                    </select>

                    <p class="infor">Chuyên ngành <span>(*)</span></p>
                    <select id="edit-course-major-select">
                        <option value="">-- Chọn Khoa/Viện trước --</option>
                    </select>

                    <p class="infor">Cấu trúc điểm</p>
                    <select id="edit-course-grading-scheme-select">
                        <option value="">-- Chọn cấu trúc điểm --</option>
                    </select>

                    <div class="thaotac">
                        <button class="Chinhsua" id="update-course-btn">Cập nhật</button>
                        <button class="Huy">Hủy</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Javascript -->
    <script src="{{ asset('js/admin/admin.js') }}"></script>
    <script src="{{ asset('js/admin/adminhocthuat.js') }}"></script>
</body>

</html>