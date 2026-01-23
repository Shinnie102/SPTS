<link rel="stylesheet" href="{{ asset('css/menu_lecturer.css') }}">
<div id="menu_lecturer" class="menu_lecturer">
    <div id="menu">
        <a href="{{ route('student.dashboard') }}">
            <div class="frame_menu {{ Request::routeIs('student.dashboard') ? 'menu_active' : '' }}">
                <i class="fa-solid fa-house"></i>
                <p>Trang chủ</p>
            </div>
        </a>
        <a href="{{ route('student.study') }}">
            <div class="frame_menu {{ Request::routeIs('student.study') ? 'menu_active' : '' }}">
                <i class="fa-solid fa-graduation-cap"></i>
                <p>Học tập</p>
            </div>
        </a>
        <a href="{{ route('student.history') }}">
            <div class="frame_menu {{ Request::routeIs('student.history') ? 'menu_active' : '' }}">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <p>Chuyên cần</p>
            </div>
        </a>
    </div>
    <form method="POST" action="{{ route('logout') }}" class="frame_logout">
        @csrf
        <button type="submit" style="background: none; border: none; cursor: pointer; display: flex; align-items: center; gap: 10px; width: 100%;">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
            <p>Đăng xuất</p>
        </button>
    </form>
</div>