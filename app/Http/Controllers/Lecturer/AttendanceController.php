<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceStatus;
use App\Models\ClassMeeting;
use App\Models\ClassSection;
use App\Models\Enrollment;
use App\Services\Lecturer\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AttendanceController extends Controller
{
    /**
     * Hiển thị trang điểm danh
     */
    public function attendance($id, AttendanceService $attendanceService)
    {
        $lecturerId = Auth::id();

        // Lưu lớp đã chọn vào session
        session(['selected_class_id' => $id]);

        $viewData = $attendanceService->getAttendancePageData((int) $id, (int) $lecturerId);

        return view('lecturer.attendance', $viewData);
    }

    /**
     * Lấy dữ liệu điểm danh cho buổi học
     */
    public function getAttendanceData(Request $request, $classId, $meetingId, AttendanceService $attendanceService)
    {
        $lecturerId = Auth::id();

        $data = $attendanceService->getAttendanceData((int) $classId, (int) $meetingId, (int) $lecturerId);
        return response()->json($data);
    }

    /**
     * Lưu điểm danh
     */
    public function saveAttendance(Request $request, $classId, AttendanceService $attendanceService)
    {
        $lecturerId = Auth::id();

        [$status, $payload] = $attendanceService->saveAttendance($request, (int) $classId, (int) $lecturerId);
        return response()->json($payload, $status);
    }
}
