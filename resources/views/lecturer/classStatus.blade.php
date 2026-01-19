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
                <div class="attendance-container">

@include('lecturer.attendance_header', [
    'currentClass' => $currentClass,
    'classes' => $classes,
    'currentTab' => 'status'
])

                <style>
                    /* Modal-only styling (scoped) to avoid altering the legacy page UI */
                    #exportScoresModal.modal {
                        display: none;
                        position: fixed;
                        inset: 0;
                        z-index: 1055;
                        overflow-y: auto;
                        padding: 24px 12px;
                    }
                    #exportScoresModal.modal.show {
                        display: block;
                    }
                    #exportScoresModal .modal-dialog {
                        max-width: 520px;
                        margin: 0 auto;
                    }
                    #exportScoresModal .modal-content {
                        background: #fff;
                        border-radius: 14px;
                        overflow: hidden;
                        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
                        border: 1px solid #e5e7eb;
                    }
                    #exportScoresModal .modal-header,
                    #exportScoresModal .modal-body,
                    #exportScoresModal .modal-footer {
                        padding: 14px 16px;
                    }
                    #exportScoresModal .modal-header {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        gap: 12px;
                        border-bottom: 1px solid #e5e7eb;
                    }
                    #exportScoresModal .modal-title {
                        margin: 0;
                        font-size: 18px;
                        font-weight: 600;
                        color: #000;
                    }
                    #exportScoresModal .modal-close {
                        background: transparent;
                        border: 0;
                        font-size: 22px;
                        line-height: 1;
                        cursor: pointer;
                        color: rgba(0, 0, 0, 0.65);
                        padding: 0 4px;
                    }
                    #exportScoresModal .modal-close:hover {
                        color: rgba(0, 0, 0, 0.9);
                    }
                    #exportScoresModal .export-type {
                        display: flex;
                        flex-direction: column;
                        gap: 10px;
                        margin-top: 10px;
                    }
                    #exportScoresModal .export-option {
                        display: flex;
                        align-items: center;
                        gap: 10px;
                    }
                    #exportScoresModal .modal-note {
                        margin-top: 12px;
                        font-size: 12px;
                        color: rgba(0, 0, 0, 0.6);
                    }
                    #exportScoresModal .modal-footer {
                        border-top: 1px solid #e5e7eb;
                        display: flex;
                        justify-content: flex-end;
                        gap: 10px;
                    }
                    #exportScoresModal .btn-secondary-lite {
                        border: 1px solid #dedcdc;
                        background: #fff;
                        color: rgba(0, 0, 0, 0.75);
                    }
                    #exportScoresModal .btn-secondary-lite:hover {
                        background: #f9f9f9;
                    }
                    .modal-backdrop {
                        position: fixed;
                        inset: 0;
                        background: rgba(0, 0, 0, 0.45);
                        z-index: 1050;
                    }
                    .modal-backdrop.fade {
                        opacity: 0;
                    }
                    .modal-backdrop.show {
                        opacity: 1;
                    }
                </style>

                @php
                    $totalStudents = is_array($students ?? null) ? count($students) : 0;
                    $passedCount = is_array($students ?? null)
                        ? count(array_filter($students, fn ($s) => ($s['status_label'] ?? null) === 'Đạt'))
                        : 0;
                    $warningCount = is_array($students ?? null)
                        ? count(array_filter($students, fn ($s) => ($s['status_label'] ?? null) === 'Nguy cơ'))
                        : 0;
                    $failedCount = is_array($students ?? null)
                        ? count(array_filter($students, fn ($s) => ($s['status_label'] ?? null) === 'Không đạt'))
                        : 0;
                    $noScoreCount = is_array($students ?? null)
                        ? count(array_filter($students, fn ($s) => ($s['status_label'] ?? null) === 'Chưa có điểm'))
                        : 0;
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
                                <button class="btn btn-dark-blue" type="button" id="exportScoresBtn" data-bs-toggle="modal" data-bs-target="#exportScoresModal">
                                    <svg width="14" height="14" fill="white" viewBox="0 0 24 24">
                                        <path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z" />
                                    </svg>
                                    Xuất
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bootstrap Modal: Export scores -->
                <div class="modal fade" id="exportScoresModal" tabindex="-1" aria-labelledby="exportScoresModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content" style="border-radius: 14px; overflow:hidden;">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exportScoresModalLabel">Xuất bảng điểm</h5>
                                <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="Đóng">&times;</button>
                            </div>
                            <div class="modal-body">
                                <p style="margin-top:0; color:#475569;">Chọn định dạng xuất:</p>
                                <div class="export-type">
                                    <label class="export-option" for="exportTypeExcel">
                                        <input type="radio" name="exportType" id="exportTypeExcel" value="excel" checked>
                                        <span>Excel (.xlsx)</span>
                                    </label>
                                    <label class="export-option" for="exportTypePdf">
                                        <input type="radio" name="exportType" id="exportTypePdf" value="pdf">
                                        <span>PDF (.pdf)</span>
                                    </label>
                                </div>

                                <div class="modal-note">
                                    Ghi chú: PDF hiện là trang in (Print → Save as PDF). Excel .xlsx sẽ báo “đang phát triển” nếu chưa cài package.
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary-lite" data-bs-dismiss="modal">Hủy</button>
                                <button type="button" class="btn btn-dark-blue" id="confirmExportScoresBtn">Xuất</button>
                            </div>
                        </div>
                    </div>
                </div>

                <section class="table-section">
                    <div class="table-header-content">
                        <h2 class="table-title">Danh sách sinh viên</h2>
                        <p class="table-subtitle">
                            Tổng: <strong>{{ $totalStudents }}</strong> • Đạt: <strong>{{ $passedCount }}</strong> • Nguy cơ: <strong>{{ $warningCount }}</strong> • Không đạt: <strong>{{ $failedCount }}</strong> • Chưa có điểm: <strong>{{ $noScoreCount }}</strong>
                        </p>
                    </div>

                    @if(!is_array($students ?? null) || count($students) === 0)
                        <div class="no-data">
                            <p>Lớp hiện chưa có sinh viên hợp lệ.</p>
                        </div>
                    @else
                        <div class="table-wrapper">
                            <table class="class-table">
                                <thead>
                                    <tr>
                                        <th style="width: 72px;">STT</th>
                                        <th style="width: 160px;">Mã SV</th>
                                        <th>Họ tên</th>
                                        <th style="width: 180px;">Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($students as $index => $student)
                                        @php
                                            $label = $student['status_label'] ?? '—';
                                            $statusClass = match ($label) {
                                                'Đạt' => 'completed',
                                                'Nguy cơ' => 'pending',
                                                'Không đạt' => 'locked',
                                                'Chưa có điểm' => 'empty',
                                                default => '',
                                            };
                                        @endphp
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $student['student_code'] ?? '' }}</td>
                                            <td>{{ $student['name'] ?? '' }}</td>
                                            <td><span class="status {{ $statusClass }}">{{ $label }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>

                    <div class="back-link-container">
                        <a href="{{ route('lecturer.dashboard') }}" class="back-link">
                            <i class="fas fa-arrow-left"></i> Quay lại trang chủ
                        </a>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/lecturer/dropdown-header.js') }}"></script>
    <script>
        (function () {
            const confirmBtn = document.getElementById('confirmExportScoresBtn');
            if (!confirmBtn) return;

            const baseUrl = @json(route('lecturer.class.exportScores', ['id' => $currentClass->class_section_id]));

            confirmBtn.addEventListener('click', function () {
                const selected = document.querySelector('input[name="exportType"]:checked');
                const type = selected ? selected.value : 'excel';
                const url = baseUrl + '?type=' + encodeURIComponent(type);

                if (type === 'pdf') {
                    window.open(url, '_blank', 'noopener');
                } else {
                    window.location.href = url;
                }

                const modalEl = document.getElementById('exportScoresModal');
                if (modalEl && window.bootstrap && window.bootstrap.Modal) {
                    const inst = window.bootstrap.Modal.getInstance(modalEl);
                    if (inst) inst.hide();
                }
            });
        })();
    </script>
</body>
</html>