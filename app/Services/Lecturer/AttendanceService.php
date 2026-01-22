<?php

namespace App\Services\Lecturer;

use App\Models\Attendance;
use App\Models\AttendanceStatus;
use App\Models\ClassMeeting;
use App\Models\ClassSection;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AttendanceService
{
    public function getAttendancePageData(int $classSectionId, int $lecturerId): array
    {
        $currentClass = ClassSection::with(['courseVersion.course'])
            ->where('class_section_id', $classSectionId)
            ->where('lecturer_id', $lecturerId)
            ->firstOrFail();

        $classes = ClassSection::with(['courseVersion.course'])
            ->where('lecturer_id', $lecturerId)
            ->orderBy('class_section_id', 'desc')
            ->get();

        $meetings = ClassMeeting::where('class_section_id', $classSectionId)
            ->orderBy('meeting_date', 'asc')
            ->get();

        $enrollments = Enrollment::where('class_section_id', $classSectionId)
            ->whereIn('enrollment_status_id', [1, 2])
            ->with(['student'])
            ->get();

        $attendanceStatuses = AttendanceStatus::all()->keyBy('status_id');

        $currentMeeting = $meetings->first();
        $attendanceData = [];

        $attendanceRecords = collect();
        if ($currentMeeting) {
            $attendanceRecords = Attendance::where('class_meeting_id', $currentMeeting->class_meeting_id)
                ->get()
                ->keyBy('enrollment_id');
        }

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

        $isAttendanceLocked = false;
        if ($currentMeeting) {
            $attendanceCount = Attendance::where('class_meeting_id', $currentMeeting->class_meeting_id)->count();
            $isAttendanceLocked = $attendanceCount > 0;
        }

        return compact(
            'currentClass',
            'classes',
            'meetings',
            'currentMeeting',
            'attendanceData',
            'isAttendanceLocked'
        );
    }

    public function getAttendanceData(int $classId, int $meetingId, int $lecturerId): array
    {
        ClassSection::where('class_section_id', $classId)
            ->where('lecturer_id', $lecturerId)
            ->firstOrFail();

        $meeting = ClassMeeting::where('class_meeting_id', $meetingId)
            ->where('class_section_id', $classId)
            ->firstOrFail();

        $enrollments = Enrollment::where('class_section_id', $classId)
            ->whereIn('enrollment_status_id', [1, 2])
            ->with(['student'])
            ->get();

        $attendanceRecords = Attendance::where('class_meeting_id', $meetingId)
            ->get()
            ->keyBy('enrollment_id');

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

        $isLocked = Attendance::where('class_meeting_id', $meetingId)->count() > 0;

        return [
            'success' => true,
            'students' => $students,
            'meeting' => $meeting,
            'isLocked' => $isLocked,
        ];
    }

    /**
     * Returns tuple: [statusCode, payload]
     */
    public function saveAttendance(Request $request, int $classId, int $lecturerId): array
    {
        ClassSection::where('class_section_id', $classId)
            ->where('lecturer_id', $lecturerId)
            ->firstOrFail();

        // Không cho lưu vào buổi đã tồn tại trong nghiệp vụ hiện tại
        if ($request->filled('meeting_id')) {
            return [422, [
                'success' => false,
                'message' => 'Không thể lưu vào buổi đã tồn tại. Vui lòng tạo buổi mới và chọn ngày trước khi lưu.',
            ]];
        }

        $validated = $request->validate([
            'class_section_id' => 'nullable|integer',
            'meeting_date' => 'required|date_format:Y-m-d',
            'attendance' => 'required|array|min:1',
            'attendance.*.enrollment_id' => 'required|exists:enrollment,enrollment_id',
            'attendance.*.status' => 'required|exists:attendance_status,status_id',
        ]);

        if (isset($validated['class_section_id']) && (int) $validated['class_section_id'] !== (int) $classId) {
            return [422, [
                'success' => false,
                'message' => 'class_section_id không khớp với lớp đang thao tác.',
            ]];
        }

        // Đảm bảo các enrollment thuộc đúng lớp
        $enrollmentIds = collect($validated['attendance'])->pluck('enrollment_id')->unique()->values();
        $validCount = Enrollment::where('class_section_id', $classId)
            ->whereIn('enrollment_id', $enrollmentIds)
            ->count();

        if ($validCount !== $enrollmentIds->count()) {
            return [422, [
                'success' => false,
                'message' => 'Danh sách sinh viên không hợp lệ cho lớp này.',
            ]];
        }

        if (!Schema::hasTable('meeting_status')) {
            return [500, [
                'success' => false,
                'message' => 'Thiếu bảng meeting_status để tạo buổi điểm danh.',
            ]];
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

            return [200, [
                'success' => true,
                'message' => 'Điểm danh đã được lưu thành công',
                'created_meeting_id' => (int) $meeting->class_meeting_id,
                'meetings' => $meetings,
            ]];
        } catch (\Exception $e) {
            DB::rollBack();
            return [500, [
                'success' => false,
                'message' => 'Lỗi khi lưu điểm danh: ' . $e->getMessage(),
            ]];
        }
    }
}
