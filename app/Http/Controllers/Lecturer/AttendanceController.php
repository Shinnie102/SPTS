<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceStatus;
use App\Models\ClassMeeting;
use App\Models\ClassSection;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AttendanceController extends Controller
{
    /**
     * Hiển thị trang điểm danh
     */
    public function attendance($id)
    {
        $lecturerId = Auth::id();

        // Lưu lớp đã chọn vào session
        session(['selected_class_id' => $id]);

        // Lấy lớp hiện tại
        $currentClass = ClassSection::with(['courseVersion.course'])
            ->where('class_section_id', $id)
            ->where('lecturer_id', $lecturerId)
            ->firstOrFail();

        // Lấy tất cả lớp của giảng viên cho dropdown
        $classes = ClassSection::with(['courseVersion.course'])
            ->where('lecturer_id', $lecturerId)
            ->orderBy('class_section_id', 'desc')
            ->get();

        // Lấy danh sách buổi học của lớp
        $meetings = ClassMeeting::where('class_section_id', $id)
            ->orderBy('meeting_date', 'asc')
            ->get();

        // Lấy enrollment của sinh viên trong lớp
        $enrollments = Enrollment::where('class_section_id', $id)
            ->whereIn('enrollment_status_id', [1, 2]) // ACTIVE và COMPLETED
            ->with(['student'])
            ->get();

        // Lấy danh sách trạng thái điểm danh
        $attendanceStatuses = AttendanceStatus::all()->keyBy('status_id');

        // Nếu có buổi học, lấy dữ liệu điểm danh cho buổi đầu tiên
        $currentMeeting = $meetings->first();
        $attendanceData = [];

        $attendanceRecords = collect();
        if ($currentMeeting) {
            $attendanceRecords = Attendance::where('class_meeting_id', $currentMeeting->class_meeting_id)
                ->get()
                ->keyBy('enrollment_id');
        }

        // Luôn chuẩn bị danh sách sinh viên (kể cả khi chưa có buổi)
        foreach ($enrollments as $enrollment) {
            $attendance = $attendanceRecords->get($enrollment->enrollment_id);
            $attendanceData[] = [
                'enrollment_id' => $enrollment->enrollment_id,
                'student_id' => $enrollment->student_id,
                'student_code' => $enrollment->student->code_user,
                'name' => $enrollment->student->full_name,
                'attendance_status_id' => $attendance ? $attendance->attendance_status_id : null,
                'status_name' => $attendance ?
                    ($attendanceStatuses[$attendance->attendance_status_id]->name ?? null) : null,
            ];
        }

        // Kiểm tra xem buổi học đã có điểm danh chưa
        $isAttendanceLocked = false;
        if ($currentMeeting) {
            $attendanceCount = Attendance::where('class_meeting_id', $currentMeeting->class_meeting_id)->count();
            $isAttendanceLocked = $attendanceCount > 0;
        }

        return view('lecturer.attendance', compact(
            'currentClass',
            'classes',
            'meetings',
            'currentMeeting',
            'attendanceData',
            'isAttendanceLocked'
        ));
    }

    /**
     * Lấy dữ liệu điểm danh cho buổi học
     */
    public function getAttendanceData(Request $request, $classId, $meetingId)
    {
        $lecturerId = Auth::id();

        // Kiểm tra quyền truy cập
        $class = ClassSection::where('class_section_id', $classId)
            ->where('lecturer_id', $lecturerId)
            ->firstOrFail();

        $meeting = ClassMeeting::where('class_meeting_id', $meetingId)
            ->where('class_section_id', $classId)
            ->firstOrFail();

        // Lấy enrollment của sinh viên trong lớp
        $enrollments = Enrollment::where('class_section_id', $classId)
            ->whereIn('enrollment_status_id', [1, 2])
            ->with(['student'])
            ->get();

        // Lấy dữ liệu điểm danh hiện có
        $attendanceRecords = Attendance::where('class_meeting_id', $meetingId)
            ->get()
            ->keyBy('enrollment_id');

        // Lấy danh sách trạng thái điểm danh
        $attendanceStatuses = AttendanceStatus::all()->keyBy('status_id');

        $students = [];
        foreach ($enrollments as $enrollment) {
            $attendance = $attendanceRecords->get($enrollment->enrollment_id);
            $students[] = [
                'enrollment_id' => $enrollment->enrollment_id,
                'student_id' => $enrollment->student_id,
                'student_code' => $enrollment->student->code_user,
                'name' => $enrollment->student->full_name,
                'attendance_status_id' => $attendance ? $attendance->attendance_status_id : null,
                'status_name' => $attendance ?
                    ($attendanceStatuses[$attendance->attendance_status_id]->name ?? null) : null,
            ];
        }

        // Kiểm tra xem buổi học đã có điểm danh chưa
        $isLocked = Attendance::where('class_meeting_id', $meetingId)->count() > 0;

        return response()->json([
            'success' => true,
            'students' => $students,
            'meeting' => $meeting,
            'isLocked' => $isLocked
        ]);
    }

    /**
     * Lưu điểm danh
     */
    public function saveAttendance(Request $request, $classId)
    {
        $lecturerId = Auth::id();

        // Kiểm tra quyền truy cập
        $class = ClassSection::where('class_section_id', $classId)
            ->where('lecturer_id', $lecturerId)
            ->firstOrFail();

        // Không cho lưu vào buổi đã tồn tại trong nghiệp vụ hiện tại
        if ($request->filled('meeting_id')) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể lưu vào buổi đã tồn tại. Vui lòng tạo buổi mới và chọn ngày trước khi lưu.'
            ], 422);
        }

        $validated = $request->validate([
            'class_section_id' => 'nullable|integer',
            'meeting_date' => 'required|date_format:Y-m-d',
            'attendance' => 'required|array|min:1',
            'attendance.*.enrollment_id' => 'required|exists:enrollment,enrollment_id',
            'attendance.*.status' => 'required|exists:attendance_status,status_id',
        ]);

        if (isset($validated['class_section_id']) && (int) $validated['class_section_id'] !== (int) $classId) {
            return response()->json([
                'success' => false,
                'message' => 'class_section_id không khớp với lớp đang thao tác.'
            ], 422);
        }

        // Đảm bảo các enrollment thuộc đúng lớp
        $enrollmentIds = collect($validated['attendance'])->pluck('enrollment_id')->unique()->values();
        $validCount = Enrollment::where('class_section_id', $classId)
            ->whereIn('enrollment_id', $enrollmentIds)
            ->count();

        if ($validCount !== $enrollmentIds->count()) {
            return response()->json([
                'success' => false,
                'message' => 'Danh sách sinh viên không hợp lệ cho lớp này.'
            ], 422);
        }

        if (!Schema::hasTable('meeting_status')) {
            return response()->json([
                'success' => false,
                'message' => 'Thiếu bảng meeting_status để tạo buổi điểm danh.'
            ], 500);
        }

        try {
            DB::beginTransaction();

            $meetingStatusId = DB::table('meeting_status')
                ->whereIn('code', ['OPEN', 'ACTIVE'])
                ->orderByRaw("FIELD(code, 'OPEN', 'ACTIVE')")
                ->value('status_id');

            if (!$meetingStatusId) {
                $meetingStatusId = DB::table('meeting_status')->orderBy('status_id')->value('status_id');
            }

            if (!$meetingStatusId) {
                throw new \RuntimeException('Không tìm thấy trạng thái mặc định để tạo buổi điểm danh.');
            }

            $meeting = ClassMeeting::create([
                'class_section_id' => (int) $classId,
                'meeting_date' => $validated['meeting_date'],
                'time_slot_id' => null,
                'room_id' => null,
                'meeting_status_id' => (int) $meetingStatusId,
                'note' => null,
            ]);

            foreach ($validated['attendance'] as $row) {
                Attendance::create([
                    'enrollment_id' => $row['enrollment_id'],
                    'class_meeting_id' => $meeting->class_meeting_id,
                    'attendance_status_id' => $row['status'],
                    'marked_at' => now(),
                ]);
            }

            DB::commit();

            $meetings = ClassMeeting::where('class_section_id', $classId)
                ->orderBy('meeting_date', 'asc')
                ->orderBy('class_meeting_id', 'asc')
                ->get(['class_meeting_id', 'meeting_date'])
                ->map(function ($m) {
                    return [
                        'class_meeting_id' => (int) $m->class_meeting_id,
                        'meeting_date' => (string) $m->meeting_date,
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,
                'message' => 'Điểm danh đã được lưu thành công',
                'created_meeting_id' => (int) $meeting->class_meeting_id,
                'meetings' => $meetings,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lưu điểm danh: ' . $e->getMessage()
            ], 500);
        }
    }
}
