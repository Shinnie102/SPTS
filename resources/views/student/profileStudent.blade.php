<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PointC - Hồ sơ sinh viên</title>

    <link rel="stylesheet" href="{{ asset('css/student/globals.css') }}">
    <link rel="stylesheet" href="{{ asset('css/student/profileStudent.css') }}">
    <link rel="stylesheet" href="{{ asset('css/student/studentDashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/overall.css') }}">

    <link href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400;600&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>

@include('partials.header_student')

<div id="main">
    @include('student.menu_student')

    <div id="content">
        <main class="main-content">

            <section class="overview-section">
                <div class="header-content">
                    <div class="title-group">
                        <h2 class="section-title">Hồ sơ cá nhân</h2>
                        <p class="section-subtitle">Thông tin sinh viên</p>
                    </div>
                </div>
            </section>

            <div class="profile-layout">

                {{-- SIDEBAR --}}
                <aside class="profile-card-sidebar">
                    <div class="avatar-wrapper">
                        <div class="avatar-circle-large">
                            @if(auth()->user()->avatar)
                                <img src="{{ asset('storage/' . auth()->user()->avatar) }}" alt="Avatar">
                            @else
                                {{ mb_substr(auth()->user()->full_name, 0, 2) }}
                            @endif
                        </div>
                    </div>

                    <div class="student-info-header">
                        <h3 class="student-name-display">
                            {{ auth()->user()->full_name }}
                        </h3>
                        <p class="student-id-display">
                            {{ auth()->user()->code_user }}
                        </p>
                    </div>

                    <div class="divider"></div>

                    <div class="stats-container">
                        <div class="stat-row">
                            <div class="stat-icon-box">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="stat-text">
                                <span class="stat-label">Ngày nhập học</span>
                                <span class="stat-value">
                                    {{ auth()->user()->orientation_day
                                        ? date('d/m/Y', strtotime(auth()->user()->orientation_day))
                                        : 'Chưa cập nhật' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </aside>

                {{-- CONTENT --}}
                <div class="profile-details-column">

                    {{-- THÔNG TIN CÁ NHÂN --}}
                    <div class="detail-card">
                        <h4 class="detail-card-title">Thông tin cá nhân</h4>
                        <div class="detail-grid">

                            <div class="detail-item">
                                <label>Họ và tên</label>
                                <p>{{ auth()->user()->full_name }}</p>
                            </div>

                            <div class="detail-item">
                                <label>Ngày sinh</label>
                                <p>
                                    {{ auth()->user()->birth
                                        ? date('d/m/Y', strtotime(auth()->user()->birth))
                                        : 'Chưa cập nhật' }}
                                </p>
                            </div>

                            <div class="detail-item">
                                <label>Email</label>
                                <p>{{ auth()->user()->email }}</p>
                            </div>

                            <div class="detail-item">
                                <label>Số điện thoại</label>
                                <p>{{ auth()->user()->phone ?? 'Chưa cập nhật' }}</p>
                            </div>

                            <div class="detail-item full-width">
                                <label>Địa chỉ</label>
                                <p>{{ auth()->user()->address ?? 'Chưa cập nhật' }}</p>
                            </div>

                        </div>
                    </div>

                    {{-- THÔNG TIN HỌC TẬP --}}
                    <div class="detail-card">
                        <h4 class="detail-card-title">Thông tin học tập</h4>
                        <div class="detail-grid">

                            <div class="detail-item">
                                <label>Chuyên ngành</label>
                                <p>{{ auth()->user()->major ?? 'Chưa cập nhật' }}</p>
                            </div>

                            <div class="detail-item">
                                <label>Tên đăng nhập</label>
                                <p>{{ auth()->user()->username }}</p>
                            </div>

                        </div>
                    </div>

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

<script src="{{ asset('js/student/student.js') }}"></script>
</body>
</html>
