<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400;500;600;700&family=Roboto:wght@100;300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/header.css') }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"><div id="header" class="header">
    <a href="/">
        <div id="frame_logo">
            <img id="logo" src="{{ asset('images/logo.svg') }}" alt="">
            <span id="Name_logo">PointC</span>
        </div>
    </a>
    <div id="frame_personal">
        <i class="fa-regular fa-bell notification" id="notification"></i>
        <div id="infor">
            <div id="infor_user">
                <p id="fullName">{{ Auth::user()->full_name ?? 'Lecturer' }}</p>
                <p id="frame_MSSV">{{ Auth::user()->code_user ?? '011111' }}</p>
            </div>
            <img src="{{ asset('images/higher-education.png') }}" alt="" class="avatar">
        </div>
    </div>
</div>