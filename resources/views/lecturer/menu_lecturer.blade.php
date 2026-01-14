<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link
    href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400;500;600;700&family=Roboto:wght@100;300;400;500;700&display=swap"
    rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/menu_lecturer.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/all.min.css') }}">
<div id="menu_lecturer" class="menu_lecturer">
    <div id="menu">
        <a href="{{ route('lecturer.dashboard') }}">
            <div class="frame_menu {{ request()->routeIs('lecturer.dashboard') ? 'menu_active' : '' }}">
                <i class="fa-regular fa-house"></i>
                <p>Trang chủ</p>
            </div>
        </a>
        <a href="{{ route('lecturer.classes') }}">
            <div class="frame_menu {{ request()->routeIs('lecturer.classes') ? 'menu_active' : '' }}">
                <i class="fa-solid fa-list-check"></i>
                <p>Lớp quản lý</p>
            </div>
        </a>
    </div>
    <div class="frame_logout">
        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
        </form>
        <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
            <p>Đăng xuất</p>
        </a>
    </div>
</div>