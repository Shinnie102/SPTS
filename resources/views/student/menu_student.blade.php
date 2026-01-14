<link rel="stylesheet" href="{{ asset('css/menu_lecturer.css') }}">
<div id="menu_lecturer" class="menu_lecturer">
    <div id="menu">
        <a href="{{ route('student.dashboard') }}">
            <div class="frame_menu {{ Request::routeIs('student.dashboard') ? 'menu_active' : '' }}">
                <i class="fa-regular fa-house"></i>
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
        <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
            @csrf
            <button type="submit" class="frame_menu" style="width: 100%; background: none; border: none; text-align: left; cursor: pointer; padding: 0;">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                <p>Đăng xuất</p>
            </button>
        </form>
    </div>
</div>
