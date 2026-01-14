<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PointC - Hồ sơ giảng viên</title>
    
    <!-- CSS files -->
    <link rel="stylesheet" href="{{ asset('css/lecturer/globals.css') }}">
    <link rel="stylesheet" href="{{ asset('css/lecturer/profileLecturer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/lecturer/styleL.css') }}">
    <link rel="stylesheet" href="{{ asset('css/overall.css') }}">
    
    <!-- Fonts and Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400;500;600;700&family=Roboto:wght@100;300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                                    <img src="{{ asset('storage/' . auth()->user()->avatar) }}" alt="{{ auth()->user()->name }}">
                                @else
                                    {{ substr(auth()->user()->name, 0, 2) }}
                                @endif
                            </div>
                        </div>
                        
                        <div class="lecturer-info-header">
                            <h3 class="lecturer-name-display">{{ auth()->user()->title ?? '' }} {{ auth()->user()->name }}</h3>
                            <p class="lecturer-id-display">{{ auth()->user()->staff_id ?? 'GV000000' }}</p>
                        </div>

                        <div class="divider"></div>

                        <div class="stats-container">
                            <div class="stat-row">
                                <div class="stat-icon-box">
                                    <i class="fas fa-book-open"></i>
                                </div>
                                <div class="stat-text">
                                    <span class="stat-label">Số lớp giảng dạy</span>
                                    <span class="stat-value">{{ $classCount ?? 0 }} lớp</span>
                                </div>
                            </div>

                            <div class="stat-row">
                                <div class="stat-icon-box">
                                    <i class="fas fa-medal"></i>
                                </div>
                                <div class="stat-text">
                                    <span class="stat-label">Kinh nghiệm</span>
                                    <span class="stat-value">{{ $experienceYears ?? 0 }} năm</span>
                                </div>
                            </div>

                            <div class="stat-row">
                                <div class="stat-icon-box">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="stat-text">
                                    <span class="stat-label">Ngày bắt đầu</span>
                                    <span class="stat-value">{{ auth()->user()->start_date ? date('d/m/Y', strtotime(auth()->user()->start_date)) : '01/01/2024' }}</span>
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
                                        {{ auth()->user()->birthday ? date('d/m/Y', strtotime(auth()->user()->birthday)) : 'Chưa cập nhật' }}
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
                                    <label>Chuyên ngành</label>
                                    <p>{{ auth()->user()->specialization ?? 'Chưa cập nhật' }}</p>
                                </div>
                                <div class="detail-item">
                                    <label>Khoa</label>
                                    <p>{{ auth()->user()->department ?? 'Chưa cập nhật' }}</p>
                                </div>
                                <div class="detail-item">
                                    <label>Chức vụ</label>
                                    <p>{{ auth()->user()->position ?? 'Giảng viên' }}</p>
                                </div>
                                <div class="detail-item">
                                    <label>Học vị</label>
                                    <p>{{ auth()->user()->academic_title ?? 'Chưa cập nhật' }}</p>
                                </div>
                            </div>
                        </div>

                        @if(auth()->user()->qualifications)
                        <div class="detail-card">
                            <h4 class="detail-card-title">Bằng cấp & Chứng chỉ</h4>
                            <div class="qualifications-list">
                                @foreach(json_decode(auth()->user()->qualifications, true) as $qualification)
                                <div class="qualification-item">
                                    <i class="fas fa-graduation-cap"></i>
                                    <span>{{ $qualification }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="back-link-container">
                    <a href="{{ route('lecturer.dashboard') }}" class="back-link">
                        <i class="fas fa-arrow-left"></i> Quay lại trang chủ
                    </a>
                </div>
            </main>
        </div>
    </div>

    <!-- Javascript -->
    <script src="{{ asset('js/lecturer/lecturer.js') }}"></script>
    <script>
        // Thêm confirm dialog khi click chỉnh sửa
        document.querySelector('.edit-btn')?.addEventListener('click', function(e) {
            if (confirm('Bạn có muốn chỉnh sửa thông tin cá nhân không?')) {
                window.location.href = this.getAttribute('onclick')?.match(/'([^']+)'/)?.[1] || '#';
            }
        });
    </script>
</body>
</html>