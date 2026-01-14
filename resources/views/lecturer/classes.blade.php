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
    <!-- --------------------------------- -->
    <link
        href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400;500;600;700&family=Roboto:wght@100;300;400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <title>Danh sách lớp học phần - Giảng viên</title>
</head>

<body>
    <!-- Header -->
    @include('partials.header_lecturer')

    <div id="main">
        <!-- Menu -->
        @include('lecturer.menu_lecturer')

        <div id="content">
            <!-- Vui lòng điểu chỉnh tiêu đề, không thay đổi tên id có sẵn -->
            <h1 id="tieudechinh">Danh sách Lớp học phần</h1>
            <p id="tieudephu">Quản lý và theo dõi các lớp học của bạn</p>

            <main class="main-content">
                <!-- Class List Table - Full -->
                <section class="table-section full-table-wrapper" aria-label="Danh sách đầy đủ lớp học phần">
                    <!-- Search Section -->
                    <section class="search-section" aria-label="Tìm kiếm lớp học">
                        <div class="search-container">
                            <input 
                                type="text" 
                                id="search-class-input" 
                                class="search-input" 
                                placeholder="Nhập nội dung cần tìm (mã lớp, tên môn học...)" 
                                aria-label="Nhập nội dung cần tìm"
                            />
                            <i class="fas fa-search search-icon"></i>
                        </div>
                    </section>
                    
                    <div class="table-wrapper">
                        <table class="class-table" role="table">
                            <thead>
                                <tr role="row">
                                    <th role="columnheader">Mã môn học</th>
                                    <th role="columnheader">Tên môn học</th>
                                    <th role="columnheader">Tổng số sinh viên</th>
                                    <th role="columnheader">Trạng thái</th>
                                    <th role="columnheader">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="full-class-table-body">
                                <!-- Rows will be rendered by JavaScript -->
                                @if(isset($classes) && count($classes) > 0)
                                    @foreach($classes as $class)
                                    <tr>
                                        <td>{{ $class->course_code ?? 'N/A' }}</td>
                                        <td>{{ $class->course_name ?? 'N/A' }}</td>
                                        <td>{{ $class->total_students ?? 0 }}</td>
                                        <td>
                                            <span class="status-badge status-{{ $class->status ?? 'active' }}">
                                                {{ $class->status_label ?? 'Đang giảng dạy' }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('lecturer.class.detail', $class->id) }}" class="action-btn view-btn" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('lecturer.grading', $class->id) }}" class="action-btn grade-btn" title="Nhập điểm">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="5" class="text-center">Không có lớp học nào</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    
                    @if(isset($classes) && $classes->links())
                    <div class="pagination-container">
                        {{ $classes->links() }}
                    </div>
                    @endif
                    
                    <a href="{{ route('lecturer.dashboard') }}" class="back-link">
                        <i class="fas fa-arrow-left"></i> Quay lại trang chủ
                    </a>
                </section>
            </main>
        </div>
    </div>

    <!-- Javascript -->
    <script src="{{ asset('js/lecturer/data.js') }}"></script>
    <script src="{{ asset('js/lecturer/styleClass.js') }}"></script>
    <script>
        // Tìm kiếm lớp học
        document.getElementById('search-class-input')?.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#full-class-table-body tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    </script>
</body>
</html>