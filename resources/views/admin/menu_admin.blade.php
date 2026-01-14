<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link
    href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400;500;600;700&family=Roboto:wght@100;300;400;500;700&display=swap"
    rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/menu_lecturer.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/all.min.css') }}">
<div id="menu_lecturer" class="menu_lecturer">
    <div id="menu">
        <a href="{{ route('admin.dashboard') }}">
            <div class="frame_menu menu_active">
                <i class="fa-regular fa-house "></i>
                <p>Trang chủ</p>
            </div>
        </a>
        <a href="{{ route('admin.users') }}">
            <div class="frame_menu">
                <i class="fa-regular fa-user"></i>
                <p>Người dùng</p>
            </div>
        </a>
        
        <a href="{{ route('admin.hocthuat') }}">
            <div class="frame_menu">
                <i class="fa-solid fa-book-open"></i>
                <p>Học thuật</p>
            </div>
        </a>
        <a href="{{ route('admin.thoigian') }}">
            <div class="frame_menu">
                <i class="fa-regular fa-calendar"></i>
                <p>Thời gian</p>
            </div>
        </a>
        <a href="{{ route('admin.lophoc') }}">
            <div class="frame_menu">
                <i class="fa-regular fa-building"></i>
                <p>Lớp học</p>
            </div>
        </a>
        <a href="{{ route('admin.quytac') }}">
            <div class="frame_menu">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <p>Quy tắc</p>
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