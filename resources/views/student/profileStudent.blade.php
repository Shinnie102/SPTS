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

                    {{-- NÚT CHỈNH SỬA --}}
                    <button type="button" id="editProfileBtn" class="btn-secondary">
                        <i class="fas fa-pen"></i> Chỉnh sửa hồ sơ
                    </button>
                </div>
            </section>

            @if(session('success'))
                <div class="alert alert-success" style="padding: 15px; margin-bottom: 20px; background: #d4edda; color: #155724; border-radius: 5px; border: 1px solid #c3e6cb; animation: slideDown 0.3s ease-out;">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('student.profile.update') }}">
            @csrf

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
                                <input type="text" name="full_name"
                                       value="{{ old('full_name', auth()->user()->full_name) }}"
                                       disabled class="profile-input editable-input">
                                @error('full_name')
                                    <small style="color: red; display: block; margin-top: 5px;">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="detail-item">
                                <label>Ngày sinh</label>
                                <input type="date" name="birth"
                                       value="{{ old('birth', auth()->user()->birth) }}"
                                       disabled class="profile-input editable-input">
                                @error('birth')
                                    <small style="color: red; display: block; margin-top: 5px;">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="detail-item">
                                <label>Email</label>
                                <p>{{ auth()->user()->email }}</p>
                                <small style="color: #888; font-size: 12px; display: block; margin-top: 3px;">
                                    Email không thể thay đổi
                                </small>
                            </div>

                            <div class="detail-item">
                                <label>Số điện thoại</label>
                                <input type="text" name="phone"
                                       value="{{ old('phone', auth()->user()->phone) }}"
                                       disabled class="profile-input editable-input"
                                       placeholder="Chưa cập nhật">
                                @error('phone')
                                    <small style="color: red; display: block; margin-top: 5px;">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="detail-item full-width">
                                <label>Địa chỉ</label>
                                <input type="text" name="address"
                                       value="{{ old('address', auth()->user()->address) }}"
                                       disabled class="profile-input editable-input"
                                       placeholder="Chưa cập nhật">
                                @error('address')
                                    <small style="color: red; display: block; margin-top: 5px;">{{ $message }}</small>
                                @enderror
                            </div>

                        </div>
                    </div>

                    {{-- THÔNG TIN HỌC TẬP --}}
                    <div class="detail-card">
                        <h4 class="detail-card-title">Thông tin học tập</h4>
                        <div class="detail-grid">

                            <div class="detail-item">
                                <label>Chuyên ngành</label>
                                {{-- ✅ LUÔN DISABLED - KHÔNG BAO GIỜ CHO SỬA --}}
                                <input type="text"
                                       value="{{ auth()->user()->major ?? 'Chưa cập nhật' }}"
                                       disabled
                                       class="profile-input"
                                       style="background-color: #e9ecef !important; cursor: not-allowed !important;">
                                <small style="color: #888; font-size: 12px; display: block; margin-top: 5px;">
                                    <i class="fas fa-lock"></i> Chuyên ngành không thể thay đổi
                                </small>
                            </div>

                            <div class="detail-item">
                                <label>Tên đăng nhập</label>
                                <p>{{ auth()->user()->username }}</p>
                                <small style="color: #888; font-size: 12px; display: block; margin-top: 3px;">
                                    Tên đăng nhập không thể thay đổi
                                </small>
                            </div>

                        </div>
                    </div>

                    {{-- ACTION --}}
                    <div class="form-actions" id="saveActions" style="display:none;">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Lưu thay đổi
                        </button>
                        <button type="button" id="cancelEditBtn" class="btn-secondary">
                            <i class="fas fa-times"></i> Hủy
                        </button>
                    </div>

                </div>
            </div>
            </form>

            <div class="back-link-container">
                <a href="{{ route('student.dashboard') }}" class="back-link">
                    <i class="fas fa-arrow-left"></i> Quay lại trang chủ
                </a>
            </div>

        </main>
    </div>
</div>

<script src="{{ asset('js/student/student.js') }}"></script>

<script>
// Lưu giá trị ban đầu để reset khi hủy
const originalValues = {};
document.querySelectorAll('.editable-input').forEach(input => {
    originalValues[input.name] = input.value;
});

// Bật chế độ chỉnh sửa
document.getElementById('editProfileBtn').addEventListener('click', function () {
    // ✅ CHỈ BẬT CÁC INPUT CÓ CLASS 'editable-input'
    document.querySelectorAll('.editable-input').forEach(input => {
        input.disabled = false;
    });

    // ❌ INPUT CHUYÊN NGÀNH KHÔNG CÓ CLASS 'editable-input' NÊN VẪN DISABLED

    document.getElementById('saveActions').style.display = 'block';
    this.style.display = 'none';
});

// Hủy chỉnh sửa
document.getElementById('cancelEditBtn').addEventListener('click', function () {
    // Reset lại giá trị ban đầu
    document.querySelectorAll('.editable-input').forEach(input => {
        input.value = originalValues[input.name];
        input.disabled = true;
    });

    document.getElementById('saveActions').style.display = 'none';
    document.getElementById('editProfileBtn').style.display = 'inline-block';
});

// Tự động ẩn thông báo success sau 5 giây
const alert = document.querySelector('.alert-success');
if (alert) {
    setTimeout(() => {
        alert.style.transition = 'opacity 0.5s';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 500);
    }, 5000);
}
</script>

<style>
@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.profile-input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
    transition: all 0.3s;
}

.profile-input:disabled {
    background-color: #f8f9fa;
    cursor: default;
}

.profile-input:not(:disabled):focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.btn-primary, .btn-secondary {
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s;
    margin-right: 10px;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-primary:hover {
    background: #0056b3;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #545b62;
}

.form-actions {
    margin-top: 20px;
    text-align: right;
}
</style>

</body>
</html>
