<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PointC - Hồ sơ sinh viên</title>
    
    <!-- CSS files -->
    <link rel="stylesheet" href="{{ asset('css/student/globals.css') }}">
    <link rel="stylesheet" href="{{ asset('css/student/profileStudent.css') }}">
    <link rel="stylesheet" href="{{ asset('css/student/studentDashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/overall.css') }}">
    
    <!-- Fonts and Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400;500;600;700&family=Roboto:wght@100;300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js (nếu cần) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>

<body>
    <!-- Header -->
    @include('partials.header_lecturer')
    
    <div id="main">
        <!-- Menu -->
        @include('lecturer.menu_lecturer')

        <div id="content">
        

            <main class="main-content">
                <section class="overview-section">
                    <div class="header-content">
                        <div class="title-group">
                            <h2 class="section-title">Hồ sơ cá nhân</h2>
                            <p class="section-subtitle">Xem thông tin cá nhân của bạn</p>
                        </div>
                        <button class="edit-btn" onclick="window.location.href='{}'">
                            Chỉnh sửa <i class="fas fa-pencil-alt"></i>
                        </button>
                    </div>
                </section>

                <div class="profile-layout">
                    <aside class="profile-card-sidebar">
                        <div class="avatar-wrapper">
                            <div class="avatar-circle-large">
                                @if(auth()->user()->avatar)
                                    <img src="{{ asset('storage/' . auth()->guard('student')->user()->avatar) }}" alt="{{ auth()->guard('student')->user()->name }}">
                                @else
                                    {{ substr(auth()->user()->name, 0, 2) }}
                                @endif
                            </div>
                        </div>
                        
                        <div class="lecturer-info-header">
                            <h3 class="lecturer-name-display">{{ auth()->user()->name }}</h3>
                            <p class="lecturer-id-display">{{ auth()->user()->student_id ?? 'SV000000' }}</p>
                        </div>

                        <div class="divider"></div>

                        <div class="stats-container">
                            <div class="stat-row">
                                <div class="stat-icon-box">
                                    <i class="fas fa-book-open"></i>
                                </div>
                                <div class="stat-text">
                                    <span class="stat-label">Số môn đang học</span>
                                    <span class="stat-value">{{ $currentCoursesCount ?? 0 }} môn</span>
                                </div>
                            </div>

                            <div class="stat-row">
                                <div class="stat-icon-box">
                                    <i class="fas fa-medal"></i>
                                </div>
                                <div class="stat-text">
                                    <span class="stat-label">GPA</span>
                                    <span class="stat-value">{{ auth()->user()->gpa ?? '0.00' }}/4.0</span>
                                </div>
                            </div>

                            <div class="stat-row">
                                <div class="stat-icon-box">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="stat-text">
                                    <span class="stat-label">Ngày bắt đầu</span>
                                    <span class="stat-value">{{ auth()->user()->start_date ? date('d/m/Y', strtotime(auth()->guard('student')->user()->start_date)) : '01/09/2023' }}</span>
                                </div>
                            </div>
                        </div>
                    </aside>

                    <div class="profile-details-column">
                        <div class="detail-card">
                            <h4 class="detail-card-title">Thông tin cá nhân</h4>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <label>Họ và tên</label>
                                    <p>{{ auth()->user()->name }}</p>
                                </div>
                                <div class="detail-item">
                                    <label>Ngày sinh</label>
                                    <div class="info-with-icon">
                                        <i class="fas fa-calendar small-icon"></i>
                                        {{ auth()->user()->birthday ? date('d/m/Y', strtotime(auth()->guard('student')->user()->birthday)) : 'Chưa cập nhật' }}
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <label>Email</label>
                                    <div class="info-with-icon">
                                        <i class="fas fa-envelope small-icon"></i>
                                        {{ auth()->user()->email }}
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <label>Số điện thoại</label>
                                    <div class="info-with-icon">
                                        <i class="fas fa-phone small-icon"></i>
                                        {{ auth()->user()->phone ?? 'Chưa cập nhật' }}
                                    </div>
                                </div>
                                <div class="detail-item full-width">
                                    <label>Địa chỉ</label>
                                    <div class="info-with-icon">
                                        <i class="fas fa-map-marker-alt small-icon"></i>
                                        {{ auth()->user()->address ?? 'Chưa cập nhật' }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="detail-card">
                            <h4 class="detail-card-title">Thông tin học thuật</h4>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <label>Lớp học</label>
                                    <p>{{ auth()->user()->class_name ?? 'Chưa cập nhật' }}</p>
                                </div>
                                <div class="detail-item">
                                    <label>Khoa</label>
                                    <p>{{ auth()->user()->faculty ?? 'Chưa cập nhật' }}</p>
                                </div>
                                <div class="detail-item">
                                    <label>Chuyên ngành</label>
                                    <p>{{ auth()->user()->major ?? 'Chưa cập nhật' }}</p>
                                </div>
                                <div class="detail-item">
                                    <label>GPA</label>
                                    <p>{{ auth()->user()->gpa ?? 'Chưa cập nhật' }}/4.0</p>
                                </div>
                            </div>
                        </div>

                        @if(auth()->user()->achievements)
                        <div class="detail-card">
                            <h4 class="detail-card-title">Thành tích</h4>
                            <div class="qualifications-list">
                                @foreach(json_decode(auth()->user()->achievements, true) as $achievement)
                                <div class="qualification-item">
                                    <i class="fas fa-trophy"></i>
                                    <span>{{ $achievement }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="back-link-container">
                    <a href="{{ route('student.dashboard') }}" class="back-link">
                        <i class="fas fa-arrow-left"></i> Quay lại trang chủ
                    </a>
                </div>
            </main>
        </div>
    </div>

    <!-- Javascript -->
    <script src="{{ asset('js/student/student.js') }}"></script>
    <script src="{{ asset('js/student/studentAlertWarning.js') }}"></script>
    <script src="{{ asset('js/student/studentCharts.js') }}"></script>
    <script>
        // Thêm confirm dialog khi click chỉnh sửa
        document.querySelector('.edit-btn')?.addEventListener('click', function(e) {
            if (confirm('Bạn có muốn chỉnh sửa thông tin cá nhân không?')) {
                window.location.href = this.getAttribute('onclick')?.match(/'([^']+)'/)?.[1] || '{}';
            }
        });
    </script>
</body>
</html>