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
                        <form method="GET" action="{{ route('lecturer.classes') }}" class="search-form">
                            <div class="search-container">
                                <input 
                                    type="text" 
                                    name="search"
                                    id="search-class-input" 
                                    class="search-input" 
                                    placeholder="Nhập nội dung cần tìm (mã lớp, tên môn học...)" 
                                    aria-label="Nhập nội dung cần tìm"
                                    value="{{ request('search') }}"
                                />
                                <button type="submit" class="search-btn" aria-label="Tìm kiếm">
                                    <i class="fas fa-search search-icon"></i>
                                </button>
                            </div>
                        </form>
                    </section>
                    
                    @if(session('success'))
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> {{ session('success') }}
                        </div>
                    @endif
                    
                    @if(session('error'))
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                        </div>
                    @endif
                    
                    <div class="table-wrapper">
                        <table class="class-table" role="table">
                            <thead>
                                <tr role="row">
                                    <th role="columnheader">Mã lớp</th>
                                    <th role="columnheader">Mã môn học</th>
                                    <th role="columnheader">Tên môn học</th>
                                    <th role="columnheader">Tổng số sinh viên</th>
                                    <th role="columnheader">Trạng thái</th>
                                    <th role="columnheader">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="full-class-table-body">
                                @forelse($classes as $class)
                                <tr>
                                    <td>{{ $class->class_code }}</td>
                                    <td>{{ $class->course_code }}</td>
                                    <td>{{ $class->course_name }}</td>
                                    <td>{{ $class->valid_enrollments_count ?? $class->total_students }}</td>                                    <td>
                                        <span class="status-badge {{ $class->status_class }}">
                                            {{ $class->status_name }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="{{ route('lecturer.attendance', $class->class_section_id) }}" class="action-btn view-btn" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('lecturer.grading', $class->class_section_id) }}" class="action-btn grade-btn" title="Nhập điểm">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center no-data">
                                        <i class="fas fa-inbox fa-2x"></i>
                                        <p>Không có lớp học nào</p>
                                        @if(request('search'))
                                            <p class="search-hint">Thử với từ khóa tìm kiếm khác</p>
                                        @endif
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    @if($classes->hasPages())
                    <div class="pagination-container">
                        <div class="pagination-info">
                            Hiển thị lớp {{ ($classes->currentPage() - 1) * $classes->perPage() + 1 }} 
                            đến {{ min($classes->currentPage() * $classes->perPage(), $classes->total()) }} 
                            trong tổng số {{ $classes->total() }} lớp
                        </div>
                        
                        <div class="pagination-links">
                            {{ $classes->withQueryString()->links('pagination::bootstrap-4') }}
                        </div>
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
    <script src="{{ asset('js/lecturer/styleClass.js') }}"></script>
    <script>
        // Focus vào ô tìm kiếm nếu có tham số search
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search-class-input');
            if (searchInput && '{{ request('search') }}') {
                searchInput.focus();
                searchInput.select();
            }
        });
    </script>
</body>
</html>