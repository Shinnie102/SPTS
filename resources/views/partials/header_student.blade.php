<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400;500;600;700&family=Roboto:wght@100;300;400;500;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="{{ asset('css/header.css') }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

@php
    // ✅ CHỐNG LỖI: luôn đảm bảo có notifications
    $notifications = $notifications ?? [];
@endphp

<div id="header" class="header">
    <a href="{{ route('student.dashboard') }}">
        <div id="frame_logo">
            <img id="logo" src="{{ asset('images/logo.svg') }}" alt="">
            <span id="Name_logo">PointC</span>
        </div>
    </a>

    <div id="frame_personal">

        <!-- 🔔 NOTIFICATION -->
        <div class="notification-wrapper" style="position: relative; margin-right: 20px;">
            <div id="bellContainer"
                 style="cursor:pointer;width:40px;height:40px;display:flex;
                 align-items:center;justify-content:center;
                 background:#f0f0f0;border-radius:50%;position:relative;">
                <span style="font-size:22px;">🔔</span>

                @if(count($notifications) > 0)
                    <span style="position:absolute;top:-4px;right:-4px;
                        background:red;color:white;font-size:11px;
                        padding:2px 6px;border-radius:12px;">
                        {{ count($notifications) }}
                    </span>
                @endif
            </div>

            <!-- Dropdown -->
            <div id="notificationDropdown"
                 style="position:absolute;top:50px;right:0;width:360px;
                 background:white;border-radius:10px;
                 box-shadow:0 4px 20px rgba(0,0,0,0.2);
                 display:none;z-index:999999;">
                <div style="padding:20px;">
                    <h3 style="margin-bottom:15px;">Thông báo</h3>

                    @forelse($notifications as $noti)
                        <div style="padding:15px;border-radius:8px;margin-bottom:10px;
                            background:
                            {{ $noti['type'] === 'danger' ? '#f8d7da' :
                               ($noti['type'] === 'warning' ? '#fff3cd' : '#e7f3ff') }};">
                            <strong>{{ $noti['title'] ?? 'Thông báo' }}</strong><br>
                            <small>{{ $noti['message'] ?? '' }}</small>
                        </div>
                    @empty
                        <p style="text-align:center;color:#777;">
                            Không có thông báo mới
                        </p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- 👤 PROFILE -->
        <a href="{{ route('student.profile') }}" style="text-decoration:none;color:inherit;">
            <div id="infor">
                <div id="infor_user">
                    <p id="fullName">
                        {{ Auth::user()->full_name ?? 'Student' }}
                    </p>
                    <p id="frame_MSSV">
                        {{ Auth::user()->code_user ?? '011111' }}
                    </p>
                </div>

                <img
                    src="{{ Auth::user()->avatar
                        ? asset('storage/' . Auth::user()->avatar)
                        : asset('images/higher-education.png') }}"
                    alt="Avatar"
                    class="avatar">
            </div>
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const bell = document.getElementById('bellContainer');
    const dropdown = document.getElementById('notificationDropdown');

    bell.addEventListener('click', function (e) {
        e.stopPropagation();
        dropdown.style.display =
            dropdown.style.display === 'block' ? 'none' : 'block';
    });

    document.addEventListener('click', function (e) {
        if (!bell.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
});
</script>
