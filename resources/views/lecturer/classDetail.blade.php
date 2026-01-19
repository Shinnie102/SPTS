@include('lecturer.menu_lecturer')

<div class="container mt-4">
    <h3>Chi tiết lớp học phần</h3>

    <div class="card mt-3">
        <div class="card-body">
            <div><strong>Mã lớp:</strong> {{ $currentClass->class_code ?? '—' }}</div>
            <div><strong>Học phần:</strong> {{ $currentClass->courseVersion->course->course_name ?? '—' }}</div>
            <div><strong>Mã học phần:</strong> {{ $currentClass->courseVersion->course->course_code ?? '—' }}</div>
            <div><strong>Học kỳ:</strong> {{ $currentClass->semester->semester_name ?? '—' }}</div>
            <div><strong>Trạng thái lớp:</strong> {{ $currentClass->status->name ?? '—' }}</div>
        </div>
    </div>

    <div class="mt-4">
        <a class="btn btn-primary" href="{{ route('lecturer.attendance', ['id' => $currentClass->class_section_id]) }}">Điểm danh</a>
        <a class="btn btn-success" href="{{ route('lecturer.grading', ['id' => $currentClass->class_section_id]) }}">Nhập điểm</a>
        <a class="btn btn-warning" href="{{ route('lecturer.class.status', ['id' => $currentClass->class_section_id]) }}">Trạng thái</a>
        <a class="btn btn-info" href="{{ route('lecturer.report', ['id' => $currentClass->class_section_id]) }}">Báo cáo</a>
    </div>

    <div class="mt-4">
        <a href="{{ route('lecturer.classes') }}">← Quay lại danh sách lớp</a>
    </div>
</div>
