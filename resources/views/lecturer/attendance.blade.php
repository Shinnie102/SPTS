<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- hạn chế đụng vào file overall.css -->
    <link rel="stylesheet" href="{{ asset('css/lecturer/styleL.css') }}">
    <link rel="stylesheet" href="{{ asset('css/lecturer/styleClass.css') }}">
    <link rel="stylesheet" href="{{ asset('css/overall.css') }}">
    <link rel="stylesheet" href="{{ asset('css/lecturer/dropdown-header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/lecturer/attendance.css') }}">

    <!-- --------------------------------- -->
    <link
        href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400;500;600;700&family=Roboto:wght@100;300;400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <title>PointC - Điểm danh lớp học phần</title>
</head>

<body>
    <!-- Header -->
    @include('partials.header_lecturer')

    <div id="main">
        <!-- Menu -->
        @include('lecturer.menu_lecturer')

        <div id="content">
            <!-- Vui lòng điểu chỉnh tiêu đề, không thay đổi tên id có sẵn -->
            <h1 id="tieudechinh">Danh sách lớp học phần</h1>
            <p id="tieudephu">Quản lý và theo dõi các lớp học của bạn</p>

            <main class="main-content">
                <!-- Attendance Container -->
                @include('lecturer.attendance_header', [
                    'currentClass' => $currentClass,
                    'classes' => $classes,
                    'currentTab' => 'attendance'
                ])

                <!-- Khối thông báo Khóa dữ liệu -->
                @if($isAttendanceLocked)
                <div class="lock-status-container">
                    <div class="lock-header-row">
                        <div>
                            <h2 class="lock-main-title">Khóa dữ liệu điểm danh</h2>
                            <p class="lock-description">Buổi học này đã có dữ liệu điểm danh. Bạn chỉ có thể xem, không thể chỉnh sửa.</p>
                        </div>
                        <div class="lock-status-icon">
                            <img src="{{ asset('lecturer/img/lock-gray.svg') }}" alt="Khóa">
                        </div>
                    </div>
                    <div class="lock-warning-banner">
                        <div class="warning-icon-circle">
                            <img src="{{ asset('lecturer/img/warning-icon.png') }}" alt="Cảnh báo">
                        </div>
                        <div class="warning-text-box">
                            <h3 class="warning-heading">Không thể sửa điểm danh</h3>
                            <p class="warning-detail">Dữ liệu điểm danh cho buổi học này đã được lưu và khóa. Để chỉnh sửa, vui lòng liên hệ quản trị viên.</p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Hàng 3: Buổi điểm danh + Stats + Nút Lưu -->
                <div class="action-row">
                    <!-- Buổi điểm danh -->
                    <div class="session-filter">
                        <label for="session-select">Buổi điểm danh</label>
                        <div class="select-wrapper">
                            <select id="session-select" data-class-id="{{ $currentClass->class_section_id }}">
                                @if($meetings->count() > 0)
                                    @foreach($meetings as $meeting)
                                        <option value="{{ $meeting->class_meeting_id }}" 
                                                {{ $currentMeeting && $currentMeeting->class_meeting_id == $meeting->class_meeting_id ? 'selected' : '' }}
                                                data-meeting-info="Buổi {{ $loop->iteration }} - Ngày {{ $meeting->meeting_date->format('d/m/Y') }}">
                                            Buổi {{ $loop->iteration }} - Ngày {{ $meeting->meeting_date->format('d/m/Y') }}
                                        </option>
                                    @endforeach
                                @else
                                    <option value="" selected>Chưa có buổi điểm danh</option>
                                @endif
                            </select>
                            <div class="select-arrow">▼</div>
                        </div>

                        <button type="button" id="add-meeting-btn" class="add-meeting-btn" title="Tạo buổi điểm danh mới">+</button>

                        <input type="date" id="meeting-date-picker" class="meeting-date-picker" style="display:none;" />
                    </div>

                    <!-- Stats - Giữ nguyên format cũ -->
                    <div class="attendance-stats">
                        <div class="stat-box">
                            <span>Có mặt:</span>
                            <span data-count="present">0</span>
                        </div>
                        <div class="stat-box">
                            <span>Vắng:</span>
                            <span data-count="absent">0</span>
                        </div>
                        <div class="stat-box">
                            <span>Đi muộn:</span>
                            <span data-count="late">0</span>
                        </div>
                        <div class="stat-box">
                            <span>Có phép:</span>
                            <span data-count="excused">0</span>
                        </div>
                        <div class="stat-box">
                            <span>Tổng số:</span>
                            <span data-count="total">0</span>
                        </div>
                    </div>

                    <!-- Nút Lưu (chỉ hiển thị khi chưa khóa) -->
                    <div class="save-btn-wrapper">
                        <button id="save-attendance-btn" class="save-btn" {{ $isAttendanceLocked ? 'disabled' : '' }}>
                            <span> Lưu điểm danh</span>
                        </button>
                    </div>
                </div>

                <!-- Bảng điểm danh với thanh cuộn -->
                <div class="attendance-table-container @if($isAttendanceLocked) table-locked @endif">
                    <div class="attendance-table">
                        <div class="table-header">
                            <div>STT</div>
                            <div>Tên sinh viên</div>
                            <div>Mã số SV</div>
                            <div>Trạng thái</div>
                        </div>
                        
                        <div id="attendance-table-body">
                            @foreach($attendanceData as $index => $student)
                                <div class="table-row" data-enrollment-id="{{ $student['enrollment_id'] }}">
                                    <div>{{ $index + 1 }}</div>
                                    <div>{{ $student['name'] }}</div>
                                    <div>{{ $student['student_code'] }}</div>
                                    <div class="status-buttons">
                                        <button class="status-btn {{ $student['attendance_status_id'] == 1 ? 'active' : '' }}" 
                                                data-status="present" 
                                                data-status-id="1"
                                                {{ $isAttendanceLocked ? 'disabled' : '' }}>
                                            Có mặt
                                        </button>
                                        <button class="status-btn {{ $student['attendance_status_id'] == 2 ? 'active' : '' }}" 
                                                data-status="absent" 
                                                data-status-id="2"
                                                {{ $isAttendanceLocked ? 'disabled' : '' }}>
                                            Vắng
                                        </button>
                                        <button class="status-btn {{ $student['attendance_status_id'] == 3 ? 'active' : '' }}" 
                                                data-status="late" 
                                                data-status-id="3"
                                                {{ $isAttendanceLocked ? 'disabled' : '' }}>
                                            Đi muộn
                                        </button>
                                        <button class="status-btn {{ $student['attendance_status_id'] == 4 ? 'active' : '' }}" 
                                                data-status="excused" 
                                                data-status-id="4"
                                                {{ $isAttendanceLocked ? 'disabled' : '' }}>
                                            Có phép
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Link quay lại -->
                <div class="back-link-container">
                    <a href="{{ route('lecturer.dashboard') }}" class="back-link">
                        <i class="fas fa-arrow-left"></i> Quay lại trang chủ
                    </a>
                </div>
            </main>
        </div>
    </div>

    <!-- Javascript -->
    <script>
        // Truyền dữ liệu từ PHP sang JS
        window.attendanceData = @json($attendanceData);
        window.currentMeetingId = @json($currentMeeting ? $currentMeeting->class_meeting_id : null);
        window.currentClassId = @json($currentClass->class_section_id);
        window.isLocked = @json($isAttendanceLocked);
        window.csrfToken = '{{ csrf_token() }}';
        window.attendanceStatusMap = {
            1: 'present',
            2: 'absent',
            3: 'late',
            4: 'excused'
        };
    </script>
    <script src="{{ asset('js/lecturer/dropdown-header.js') }}"></script>
    <script src="{{ asset('js/lecturer/attendance.js') }}"></script>
</body>
</html>