<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PointC - Hồ sơ giảng viên</title>

    <link rel="stylesheet" href="{{ asset('css/lecturer/globals.css') }}">
    <link rel="stylesheet" href="{{ asset('css/lecturer/profileLecturer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/lecturer/styleL.css') }}">
    <link rel="stylesheet" href="{{ asset('css/overall.css') }}">

    <link href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400;600&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>

{{-- CHỈ INCLUDE 1 LẦN --}}
@include('partials.header_lecturer')

<div id="main">
    @include('lecturer.menu_lecturer')

    <div id="content">
        <main class="main-content">

        {{-- THÔNG BÁO THÀNH CÔNG --}}
        @if(session('success'))
            <div class="alert alert-success" style="padding: 15px; margin-bottom: 20px; background: #d4edda; color: #155724; border-radius: 5px;">
                <i class="fas fa-check-circle"></i> {{ session('success') }}
            </div>
        @endif

        <section class="overview-section">
            <div class="header-content">
                <div class="title-group">
                    <h2 class="section-title">Hồ sơ cá nhân</h2>
                    <p class="section-subtitle">Xem thông tin giảng viên</p>
                </div>

                <button id="editBtn" class="btn-secondary">
                    <i class="fas fa-pen"></i> Chỉnh sửa
                </button>
            </div>
        </section>

        <div class="profile-layout">

        {{-- SIDEBAR --}}
        <aside class="profile-card-sidebar">
            <div class="avatar-wrapper">
                <div class="avatar-circle-large">
                    @if(auth()->user()->avatar)
                        <img src="{{ asset('storage/' . auth()->user()->avatar) }}">
                    @else
                        {{ mb_substr(auth()->user()->full_name, 0, 2) }}
                    @endif
                </div>
            </div>

            <div class="lecturer-info-header">
                <h3>{{ auth()->user()->full_name }}</h3>
                <p>{{ auth()->user()->code_user }}</p>
            </div>
        </aside>

        {{-- CONTENT --}}
        <div class="profile-details-column">

        <form id="profileForm"
              action="{{ route('lecturer.profile.update') }}"
              method="POST">
        @csrf

        {{-- THÔNG TIN CÁ NHÂN --}}
        <div class="detail-card">
        <h4 class="detail-card-title">Thông tin cá nhân</h4>
        <div class="detail-grid">

        <div class="detail-item">
        <label>Họ và tên</label>
        <p class="view">{{ auth()->user()->full_name }}</p>
        <input class="edit profile-input" type="text" name="full_name"
               value="{{ auth()->user()->full_name }}" hidden>
        @error('full_name')
            <small style="color: red; display: block; margin-top: 5px;">{{ $message }}</small>
        @enderror
        </div>

        <div class="detail-item">
        <label>Ngày sinh</label>
        <p class="view">
        {{ auth()->user()->birth ? date('d/m/Y', strtotime(auth()->user()->birth)) : 'Chưa cập nhật' }}
        </p>
        <input class="edit profile-input" type="date" name="birth"
               value="{{ auth()->user()->birth }}" hidden>
        @error('birth')
            <small style="color: red; display: block; margin-top: 5px;">{{ $message }}</small>
        @enderror
        </div>

        <div class="detail-item">
        <label>Email</label>
        <p>{{ auth()->user()->email }}</p>
        </div>

        <div class="detail-item">
        <label>Số điện thoại</label>
        <p class="view">{{ auth()->user()->phone ?? 'Chưa cập nhật' }}</p>
        <input class="edit profile-input" type="text" name="phone"
               value="{{ auth()->user()->phone }}" hidden>
        @error('phone')
            <small style="color: red; display: block; margin-top: 5px;">{{ $message }}</small>
        @enderror
        </div>

        <div class="detail-item full-width">
        <label>Địa chỉ</label>
        <p class="view">{{ auth()->user()->address ?? 'Chưa cập nhật' }}</p>
        <input class="edit profile-input" type="text" name="address"
               value="{{ auth()->user()->address }}" hidden>
        @error('address')
            <small style="color: red; display: block; margin-top: 5px;">{{ $message }}</small>
        @enderror
        </div>

        </div>
        </div>

        {{-- HỌC THUẬT --}}
        <div class="detail-card">
        <h4 class="detail-card-title">Thông tin học thuật</h4>
        <div class="detail-grid">

        <div class="detail-item">
        <label>Chuyên ngành</label>
        {{-- ✅ CHỈ HIỂN THỊ, KHÔNG CHO SỬA --}}
        <p>{{ auth()->user()->major ?? 'Chưa cập nhật' }}</p>
        <small style="color: #888; font-size: 12px; display: block; margin-top: 5px;">
            <i class="fas fa-lock"></i> Chuyên ngành không thể thay đổi
        </small>
        </div>

        <div class="detail-item">
        <label>Tên đăng nhập</label>
        <p>{{ auth()->user()->username }}</p>
        </div>

        </div>
        </div>

        <div id="actionButtons" hidden>
        <button type="submit" class="btn-primary">
        <i class="fas fa-save"></i> Lưu
        </button>
        <button type="button" id="cancelBtn" class="btn-secondary">
        Huỷ
        </button>
        </div>

        </form>
        </div>
        </div>

        </main>
    </div>
</div>

<script>
const editBtn = document.getElementById('editBtn');
const cancelBtn = document.getElementById('cancelBtn');
const views = document.querySelectorAll('.view');
const edits = document.querySelectorAll('.edit');
const actions = document.getElementById('actionButtons');

editBtn.onclick = () => {
    views.forEach(v => v.hidden = true);
    edits.forEach(e => e.hidden = false);
    actions.hidden = false;
    editBtn.hidden = true;
};

cancelBtn.onclick = () => {
    views.forEach(v => v.hidden = false);
    edits.forEach(e => e.hidden = true);
    actions.hidden = true;
    editBtn.hidden = false;
};
</script>

</body>
</html>
