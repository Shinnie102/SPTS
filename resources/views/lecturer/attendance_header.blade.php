<script>
    window.ASSETS = {
        searchIcon: "{{ asset('lecturer/img/search-icon.svg') }}"
    };
</script>
<!-- attendance_header.blade.php -->
<!-- Attendance Container -->
<div class="attendance-container">
    <!-- Hàng 1: Chọn lớp học phần -->
    <div class="class-row">
        <div class="filter-group">
            <label for="class-select">Lớp học phần</label>
            <div class="select-wrapper">
                <select id="class-select">
                    @if(isset($classes) && $classes->count() > 0)
                        @foreach($classes as $class)
                            <option value="{{ $class->class_section_id }}"
                                {{ (isset($currentClass) && $currentClass->class_section_id == $class->class_section_id) ? 'selected' : '' }}>
                                {{ $class->class_code }} - {{ optional($class->courseVersion->course)->course_name ?? 'Chưa có tên môn' }}
                            </option>
                        @endforeach
                    @else
                        <option value="">Không có lớp nào</option>
                    @endif
                </select>
                <div class="select-arrow">▼</div>
            </div>
        </div>
    </div>

    <!-- Hàng 2: Thanh điều hướng tab -->
    <div class="tab-navigation-row">
        <nav class="tab-navigation">
            @if(isset($currentClass))
                <a href="{{ route('lecturer.attendance', ['id' => $currentClass->class_section_id]) }}" 
                   class="tab-item {{ request()->routeIs('lecturer.attendance') ? 'active' : '' }}">
                    Điểm danh
                </a>
                <a href="{{ route('lecturer.grading', ['id' => $currentClass->class_section_id]) }}" 
                   class="tab-item {{ request()->routeIs('lecturer.grading') ? 'active' : '' }}">
                    Nhập điểm
                </a>
                <a href="{{ route('lecturer.class.status', ['id' => $currentClass->class_section_id]) }}" 
                   class="tab-item {{ request()->routeIs('lecturer.class.status') ? 'active' : '' }}">
                    Trạng thái lớp
                </a>
                <a href="{{ route('lecturer.report', ['id' => $currentClass->class_section_id]) }}" 
                   class="tab-item {{ request()->routeIs('lecturer.report') ? 'active' : '' }}">
                    Báo cáo
                </a>
            @else
                <a href="#" class="tab-item">Điểm danh</a>
                <a href="#" class="tab-item">Nhập điểm</a>
                <a href="#" class="tab-item">Trạng thái lớp</a>
                <a href="#" class="tab-item">Báo cáo</a>
            @endif
        </nav>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const classSelect = document.getElementById('class-select');
    
    if (classSelect) {
        classSelect.addEventListener('change', function() {
            const selectedClassId = this.value;
            if (selectedClassId) {
                // Xác định trang hiện tại dựa trên URL
                const currentUrl = window.location.href;
                let newUrl;
                
                // Kiểm tra xem đang ở trang nào
                if (currentUrl.includes('/attendance') || currentUrl.includes('/class/') && !currentUrl.includes('/grading') && !currentUrl.includes('/status') && !currentUrl.includes('/report')) {
                    // Trang điểm danh
                    newUrl = "{{ route('lecturer.attendance', ['id' => '__ID__']) }}";
                } else if (currentUrl.includes('/grading')) {
                    // Trang nhập điểm
                    newUrl = "{{ route('lecturer.grading', ['id' => '__ID__']) }}";
                } else if (currentUrl.includes('/status')) {
                    // Trang trạng thái lớp
                    newUrl = "{{ route('lecturer.class.status', ['id' => '__ID__']) }}";
                } else if (currentUrl.includes('/report')) {
                    // Trang báo cáo
                    newUrl = "{{ route('lecturer.report', ['id' => '__ID__']) }}";
                } else {
                    // Mặc định về trang điểm danh
                    newUrl = "{{ route('lecturer.attendance', ['id' => '__ID__']) }}";
                }
                
                // Thay thế ID và chuyển hướng
                newUrl = newUrl.replace('__ID__', selectedClassId);
                window.location.href = newUrl;
            }
        });
    }
});
</script>