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
    <link rel="stylesheet" href="{{ asset('css/lecturer/attendance.css') }}">
    <link rel="stylesheet" href="{{ asset('css/lecturer/classStatus.css') }}">
    <!-- --------------------------------- -->
    <link
        href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400;500;600;700&family=Roboto:wght@100;300;400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <title>PointC - Trạng thái lớp học phần</title>
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
@include('lecturer.attendance_header', [
    'currentClass' => $currentClass,
    'classes' => $classes,
    'currentTab' => 'status'
])

                <style>
                    /* Ensure class selector stays visible/clickable on this page */
                    .attendance-container {
                        position: relative;
                        z-index: 50;
                    }
                    .select-wrapper,
                    #class-select {
                        position: relative;
                        z-index: 60;
                    }

                    /* attendance.css hides all .select-wrapper select; restore native select for class picker */
                    .attendance-container .select-wrapper select#class-select {
                        display: block !important;
                    }

                    .students-status-section {
                        margin-top: 24px;
                        background: #fff;
                        border-radius: 12px;
                        padding: 18px;
                        box-shadow: 0 1px 2px rgba(0,0,0,0.06);
                    }
                    .students-status-header {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        gap: 12px;
                        margin-bottom: 12px;
                    }
                    .students-status-header h2 {
                        margin: 0;
                        font-size: 18px;
                    }
                    .students-status-summary {
                        display: flex;
                        gap: 10px;
                        flex-wrap: wrap;
                        font-size: 13px;
                        color: #475569;
                    }
                    .students-status-summary .pill {
                        background: #f1f5f9;
                        padding: 6px 10px;
                        border-radius: 999px;
                    }
                    .students-status-table {
                        width: 100%;
                        border-collapse: collapse;
                        overflow: hidden;
                        border-radius: 10px;
                    }
                    .students-status-table th,
                    .students-status-table td {
                        padding: 10px 12px;
                        border-bottom: 1px solid #e2e8f0;
                        text-align: left;
                        font-size: 14px;
                    }
                    .students-status-table thead th {
                        background: #f8fafc;
                        color: #0f172a;
                        font-weight: 700;
                    }
                    .badge {
                        display: inline-block;
                        padding: 4px 10px;
                        border-radius: 999px;
                        font-size: 12px;
                        font-weight: 700;
                        line-height: 1.4;
                    }
                    .badge-studying {
                        background: #e2e8f0;
                        color: #334155;
                    }
                    .badge-warning {
                        background: #ffedd5;
                        color: #9a3412;
                    }
                    .badge-eligible {
                        background: #dcfce7;
                        color: #166534;
                    }
                    .empty-note {
                        margin: 0;
                        color: #64748b;
                        font-size: 14px;
                    }
                </style>

                @php
                    $totalStudents = is_array($students ?? null) ? count($students) : 0;
                    $eligibleCount = is_array($students ?? null) ? count(array_filter($students, fn($s) => ($s['status_label'] ?? '') === 'Đủ điều kiện')) : 0;
                    $warningCount = is_array($students ?? null) ? count(array_filter($students, fn($s) => ($s['status_label'] ?? '') === 'Cảnh báo')) : 0;
                    $studyingCount = max(0, $totalStudents - $eligibleCount - $warningCount);
                @endphp

                @if($totalStudents === 0)
                    <div style="margin-bottom: 32px; padding: 12px 14px; border-radius: 12px; background: #fff7ed; border: 1px solid #fed7aa; color: #9a3412;">
                        Lớp này hiện chưa có sinh viên hợp lệ.
                    </div>
                @endif

                <div class="dashboard-container">
                    <div class="row-top">
                        <div class="card">
                            <div class="card-header">
                                <h2 class="card-title">Trạng thái điểm danh</h2>
                                <div class="icon success">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                        <circle cx="12" cy="12" r="10" />
                                        <path d="M8 12L11 15L16 9" />
                                    </svg>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="info-row"><span>Số buổi điểm danh:</span><strong>{{ $dashboard['attendance']['done'] ?? 0 }}/{{ $dashboard['attendance']['total'] ?? 0 }}</strong></div>
                                <div class="info-row"><span>Tỉ lệ hoàn thành:</span><strong>{{ $dashboard['attendance']['percent'] ?? 0 }}%</strong></div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h2 class="card-title">Trạng thái nhập điểm</h2>
                                <div class="icon success">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                        <circle cx="12" cy="12" r="10" />
                                        <path d="M8 12L11 15L16 9" />
                                    </svg>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="info-row"><span>Đã nhập điểm:</span><strong>{{ $dashboard['grading']['done'] ?? 0 }}/{{ $dashboard['grading']['total'] ?? 0 }}</strong></div>
                                <div class="info-row"><span>Tỉ lệ hoàn thành:</span><strong>{{ $dashboard['grading']['percent'] ?? 0 }}%</strong></div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h2 class="card-title">Thông tin cập nhật</h2>
                            </div>
                            <div class="card-body">
                                <div class="info-row"><span>Lần cập nhật cuối:</span><strong>{{ $dashboard['last_updated_at'] ?? '—' }}</strong></div>
                                <div class="info-row"><span>Người cập nhật:</span><strong>{{ $dashboard['updated_by'] ?? '—' }}</strong></div>
                                <div class="info-row">
                                    <span>Trạng thái lớp:</span><strong class="status-active">{{ $dashboard['class_status_name'] ?? '—' }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row-bottom">
                        <div class="card">
                            <div class="card-header">
                                <h2 class="card-title">Khóa dữ liệu lớp học</h2>
                                <div class="icon-lock-gray">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="2">
                                        <rect x="5" y="11" width="14" height="10" rx="2" />
                                        <path d="M8 11V7a4 4 0 1 1 8 0v4" />
                                    </svg>
                                </div>
                            </div>
                            <div class="card-body action-layout">
                                <p class="note">
                                    Lưu ý: Khi khóa dữ liệu lớp, bạn sẽ không thể chỉnh sửa điểm danh hoặc điểm số. Vui lòng đảm bảo tất cả thông tin đã chính xác trước khi khóa.
                                </p>
                                <button class="btn btn-red" type="button" disabled>
                                    <svg width="14" height="14" fill="white" viewBox="0 0 24 24">
                                        <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6z" />
                                    </svg>
                                    Khóa
                                </button>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h2 class="card-title">Xuất bảng điểm</h2>
                                <div class="icon-download-gray">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="2" stroke-linecap="round">
                                        <circle cx="12" cy="12" r="10" />
                                        <path d="M12 8v8m-4-4l4 4 4-4" />
                                    </svg>
                                </div>
                            </div>
                            <div class="card-body action-layout">
                                <p class="note">Xuất bảng điểm danh và bảng điểm của lớp dưới dạng file Excel hoặc PDF.</p>
                                <button class="btn btn-dark-blue" type="button" disabled>
                                    <svg width="14" height="14" fill="white" viewBox="0 0 24 24">
                                        <path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z" />
                                    </svg>
                                    Xuất
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <section class="students-status-section">
                    <div class="students-status-header">
                        <h2>Danh sách sinh viên</h2>
                        <div class="students-status-summary" aria-label="Tóm tắt trạng thái">
                            <span class="pill">Tổng: <strong>{{ $totalStudents }}</strong></span>
                            <span class="pill">Đang học: <strong>{{ $studyingCount }}</strong></span>
                            <span class="pill">Cảnh báo: <strong>{{ $warningCount }}</strong></span>
                            <span class="pill">Đủ điều kiện: <strong>{{ $eligibleCount }}</strong></span>
                        </div>
                    </div>

                    @if(!is_array($students ?? null) || count($students) === 0)
                        <p class="empty-note">Lớp hiện chưa có sinh viên hợp lệ.</p>
                    @else
                        <table class="students-status-table">
                            <thead>
                                <tr>
                                    <th style="width: 72px;">STT</th>
                                    <th style="width: 160px;">Mã SV</th>
                                    <th>Họ tên</th>
                                    <th style="width: 160px;">Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($students as $index => $student)
                                    @php
                                        $label = $student['status_label'] ?? 'Đang học';
                                        $badgeClass = match ($label) {
                                            'Đủ điều kiện' => 'badge-eligible',
                                            'Cảnh báo' => 'badge-warning',
                                            default => 'badge-studying',
                                        };
                                    @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $student['student_code'] ?? '' }}</td>
                                        <td>{{ $student['name'] ?? '' }}</td>
                                        <td><span class="badge {{ $badgeClass }}">{{ $label }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </section>

                    <div class="back-link-container">
                        <a href="{{ route('lecturer.dashboard') }}" class="back-link">
                            <i class="fas fa-arrow-left"></i> Quay lại trang chủ
                        </a>
                    </div>
                
            </main>
        </div>
    </div>
</body>
</html>