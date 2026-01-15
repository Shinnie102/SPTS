<!-- attendance_header.blade.php -->
<!-- Attendance Container -->
<div class="attendance-container">
    <!-- Hàng 1: Chọn lớp học phần -->
    <div class="class-row">
        <div class="filter-group">
            <label for="class-select">Lớp học phần</label>
            <div class="select-wrapper">
                <select id="class-select">
                    <option value="LTM101">LTM101 - Lập trình mạng</option>
                    <option value="CS101">CS101 - Cơ sở dữ liệu</option>
                    <option value="WEB101">WEB101 - Lập trình Web</option>
                </select>
                <div class="select-arrow">▼</div>
            </div>
        </div>
    </div>

    <!-- Hàng 2: Thanh điều hướng tab -->
    <div class="tab-navigation-row">
        <nav class="tab-navigation">
            <a href="{{ route('lecturer.attendance.show') }}" class="tab-item {{ request()->routeIs('lecturer.attendance.*') ? 'active' : '' }}">Điểm danh</a>
            <a href="{{ route('lecturer.grading.show') }}" class="tab-item {{ request()->routeIs('lecturer.grading.*') ? 'active' : '' }}">Nhập điểm</a>
            <a href="{{ route('lecturer.classes.show') }}" class="tab-item {{ request()->routeIs('lecturer.classes.*') ? 'active' : '' }}">Trạng thái lớp</a>
            <a href="{{ route('lecturer.report.show') }}" class="tab-item {{ request()->routeIs('lecturer.report.*') ? 'active' : '' }}">Báo cáo</a>
        </nav>
    </div>