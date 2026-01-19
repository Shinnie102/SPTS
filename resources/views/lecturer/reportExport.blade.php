@include('lecturer.menu_lecturer')

<div class="container mt-4">
    <h3>Xuất báo cáo (tạm thời)</h3>
    <p>class_section_id: {{ $class_section_id ?? '—' }}</p>

    <div class="alert alert-info">
        Chức năng exportReport() đã được tạo. Bước tiếp theo có thể xuất PDF/Excel.
    </div>

    <a class="btn btn-secondary" href="{{ route('lecturer.report', ['id' => $class_section_id]) }}">Quay lại báo cáo</a>
</div>
