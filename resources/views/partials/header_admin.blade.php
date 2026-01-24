<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400;500;600;700&family=Roboto:wght@100;300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/header.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/all.min.css') }}">

<div id="header" class="header">
    <a href="/">
        <div id="frame_logo">
            <img id="logo" src="{{ asset('images/logo.svg') }}" alt="">
            <span id="Name_logo">PointC</span>
        </div>
    </a>

    <div id="frame_personal">

        <!-- 🔔 NOTIFICATION ADMIN -->
<div class="notification-wrapper" style="position: relative; margin-right: 20px; z-index: 99999;">
    <div id="bellContainer"
         style="
            cursor: pointer;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f0f0f0;
            border-radius: 50%;
            position: relative;
         ">
        <span style="font-size: 22px;">🔔</span>

        @if(count($notifications) > 0)
        <span style="
            position: absolute;
            top: -4px;
            right: -4px;
            background: red;
            color: white;
            font-size: 11px;
            padding: 2px 6px;
            border-radius: 12px;
        ">
            {{ count($notifications) }}
        </span>
        @endif
    </div>

    <!-- Dropdown -->
    <div id="notificationDropdown"
         style="
            position: absolute;
            top: 50px;
            right: 0;
            width: 360px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            display: none;
            z-index: 999999;
         ">
        <div style="padding: 20px;">
            <h3 style="margin: 0 0 15px 0;">Thông báo</h3>

            @forelse ($notifications as $noti)
                @php
                    $bg = match($noti['type']) {
                        'info' => '#e7f3ff',
                        'warning' => '#fff3cd',
                        'danger' => '#f8d7da',
                        default => '#f1f1f1'
                    };
                @endphp

                <div style="
                    padding: 15px;
                    background: {{ $bg }};
                    border-radius: 8px;
                    margin-bottom: 10px;
                ">
                    <strong style="display:block;">
                        {{ $noti['title'] }}
                    </strong>
                    <small>{{ $noti['message'] }}</small>
                </div>
            @empty
                <p style="color:#777;">Không có thông báo</p>
            @endforelse

        </div>
    </div>
</div>


        <!-- PROFILE -->
        <div id="infor">
            <div id="infor_user">
                <p id="fullName">{{ Auth::user()->full_name ?? 'Admin' }}</p>
                <p id="frame_MSSV">{{ Auth::user()->code_user ?? '' }}</p>
            </div>
            <img src="{{ asset('images/higher-education.png') }}" alt="" class="avatar">
        </div>

    </div>
</div>

<!-- JS TOGGLE NOTIFICATION -->
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
