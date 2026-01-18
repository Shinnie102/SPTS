@if(session('student_notifications'))
<div id="student-popup"
     style="position: fixed;
            top: 90px;
            right: 20px;
            width: 360px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,.15);
            z-index: 9999;
            padding: 20px;">

    <h3 style="margin-bottom: 15px;">Thông báo hệ thống</h3>

    @foreach(session('student_notifications') as $noti)
        <div style="padding: 15px;
                    margin-bottom: 10px;
                    border-radius: 8px;
                    background: {{ $noti['bg'] }};">
            <strong>{{ $noti['title'] }}</strong><br>
            <small>{{ $noti['message'] }}</small>
        </div>
    @endforeach
</div>

<script>
    setTimeout(() => {
        const popup = document.getElementById('student-popup');
        if (popup) popup.style.display = 'none';
    }, 5000);
</script>
@endif
