<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400;500;600;700&family=Roboto:wght@100;300;400;500;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="{{ asset('css/header.css') }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

{{-- ✅ Thêm meta CSRF token --}}
<meta name="csrf-token" content="{{ csrf_token() }}">

@php
    $notifications = $notifications ?? [];
    $hasRead = $hasRead ?? false;
@endphp

<div id="header" class="header">
    <a href="{{ route('lecturer.dashboard') }}">
        <div id="frame_logo">
            <img id="logo" src="{{ asset('images/logo.svg') }}" alt="">
            <span id="Name_logo">PointC</span>
        </div>
    </a>

    <div id="frame_personal">

        <!-- 🔔 NOTIFICATION -->
        <div class="notification-wrapper" style="position: relative; margin-right: 20px;">
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
                <span style="font-size:22px;">🔔</span>

                {{-- ✅ Chỉ hiển thị số khi chưa đọc --}}
                @if(!empty($notifications) && count($notifications) > 0 && !$hasRead)
                    <span id="notificationCount" style="
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
                    display: none;
                    position: absolute;
                    top: 50px;
                    right: 0;
                    width: 360px;
                    max-height: 500px;
                    overflow-y: auto;
                    background: white;
                    border-radius: 10px;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
                    z-index: 9999;
                 ">
                <div style="padding: 20px;">
                    <h3 style="margin-bottom: 15px;">Thông báo</h3>

                    @forelse($notifications ?? [] as $noti)
                        <div style="
                            padding: 15px;
                            margin-bottom: 10px;
                            border-radius: 8px;
                            background:
                                {{ $noti['type'] === 'danger' ? '#f8d7da' :
                                   ($noti['type'] === 'warning' ? '#fff3cd' : '#e7f3ff') }};
                        ">
                            <strong style="display:block;margin-bottom:4px;">
                                {{ $noti['title'] }}
                            </strong>
                            <small style="color:#555;">
                                {{ $noti['message'] }}
                            </small>
                        </div>
                    @empty
                        <p style="text-align: center; color: #777;">
                            Không có thông báo
                        </p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- 👤 PROFILE -->
        <a href="{{ route('lecturer.profile') }}" style="text-decoration:none;color:inherit;">
            <div id="infor">
                <div id="infor_user">
                    <p id="fullName">{{ Auth::user()->full_name ?? 'Lecturer' }}</p>
                    <p id="frame_MSSV">{{ Auth::user()->code_user ?? '' }}</p>
                </div>

                <img
                    class="avatar"
                    src="{{ Auth::user()->avatar
                        ? asset('storage/' . Auth::user()->avatar)
                        : asset('images/higher-education.png') }}"
                    alt="Avatar">
            </div>
        </a>

    </div>
</div>

<!-- ✅ JS ĐƠN GIẢN - CHỈ XỬ LÝ CLICK CHUÔNG -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const bell = document.getElementById('bellContainer');
    const dropdown = document.getElementById('notificationDropdown');
    const notificationCount = document.getElementById('notificationCount');

    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    // ✅ Click vào chuông
    bell.addEventListener('click', function (e) {
        e.stopPropagation();

        // Toggle dropdown
        const isVisible = dropdown.style.display === 'block';
        dropdown.style.display = isVisible ? 'none' : 'block';

        // ✅ Nếu có số thông báo, gọi API để ẩn nó
        if (notificationCount && !isVisible) {
            fetch('{{ route("lecturer.notifications.markAllRead") }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: JSON.stringify({})
            })
            .then(resp => resp.ok ? resp.json() : null)
            .then(response => {
                if (response && response.success) {
                    notificationCount.style.display = 'none';
                }
            })
            .catch(() => {
                // ignore
            });
        }
    });

    // Đóng dropdown khi click ra ngoài
    document.addEventListener('click', function () {
        dropdown.style.display = 'none';
    });

    // Ngăn dropdown đóng khi click vào nó
    dropdown.addEventListener('click', function(e) {
        e.stopPropagation();
    });
});
</script>
