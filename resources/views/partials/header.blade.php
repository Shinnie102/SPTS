<div id="header" class="header">
    <a href="{{ url('/') }}">
        <div id="frame_logo">
            <img id="logo" src="{{ asset('images/logo.svg') }}" alt="PointC Logo">
            <span id="Name_logo">PointC</span>
        </div>
    </a>
    <div id="frame_personal">
        <i class="fa-regular fa-bell notification" id="notification"></i>
        <div id="infor">
            <div id="infor_user">
                <p id="fullName">{{ Auth::user()->full_name ?? 'User' }}</p>
                <p id="frame_MSSV">
                    @if(Auth::user()->role->role_code === 'student')
                        MSSV: <span id="MSSV">{{ Auth::user()->code_user }}</span>
                    @else
                        Mã: <span id="MSSV">{{ Auth::user()->code_user }}</span>
                    @endif
                </p>
            </div>
            <img src="{{ Auth::user()->avatar ? asset(Auth::user()->avatar) : asset('images/higher-education.png') }}" 
                 alt="Avatar" 
                 class="avatar">
        </div>
    </div>
</div>
