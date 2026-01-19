<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        return view('student.studentDashboard');
    }

    /**
     * API: Lấy dữ liệu dashboard sinh viên
     */
    public function getDashboardData()
    {
        $studentId = Auth::id();

        /**
         * 1️⃣ GPA tích lũy
         * Công thức mẫu: AVG(score_value)
         * (Sau này bạn có thể nâng cấp theo trọng số)
         */
        $gpaTotal = DB::table('student_score')
            ->join('enrollment', 'student_score.enrollment_id', '=', 'enrollment.enrollment_id')
            ->where('enrollment.student_id', $studentId)
            ->avg('student_score.score_value');

        /**
         * 2️⃣ GPA học kỳ hiện tại
         */
        $currentSemesterId = DB::table('semester')
            ->where('status_id', 1) // học kỳ đang hoạt động
            ->value('semester_id');

        $gpaSemester = DB::table('student_score')
            ->join('enrollment', 'student_score.enrollment_id', '=', 'enrollment.enrollment_id')
            ->join('class_section', 'enrollment.class_section_id', '=', 'class_section.class_section_id')
            ->where('enrollment.student_id', $studentId)
            ->where('class_section.semester_id', $currentSemesterId)
            ->avg('student_score.score_value');

        /**
         * 3️⃣ Tín chỉ tích lũy
         */
        $totalCredits = DB::table('enrollment')
            ->join('class_section', 'enrollment.class_section_id', '=', 'class_section.class_section_id')
            ->join('course_version', 'class_section.course_version_id', '=', 'course_version.course_version_id')
            ->where('enrollment.student_id', $studentId)
            ->whereIn('enrollment.enrollment_status_id', [1, 2]) // đang học / hoàn thành
            ->sum('course_version.credit');

        /**
         * 4️⃣ Tỷ lệ chuyên cần (%)
         */
        $totalAttendance = DB::table('attendance')
            ->join('enrollment', 'attendance.enrollment_id', '=', 'enrollment.enrollment_id')
            ->where('enrollment.student_id', $studentId)
            ->count();

        $presentAttendance = DB::table('attendance')
            ->join('enrollment', 'attendance.enrollment_id', '=', 'enrollment.enrollment_id')
            ->where('enrollment.student_id', $studentId)
            ->where('attendance.attendance_status_id', 1) // có mặt
            ->count();

        $attendanceRate = $totalAttendance > 0
            ? round(($presentAttendance / $totalAttendance) * 100)
            : 0;

        return response()->json([
            'overview' => [
                'gpaTotal' => round($gpaTotal, 2),
                'gpaSemester' => round($gpaSemester, 2),
                'totalCredits' => $totalCredits,
                'attendanceRate' => $attendanceRate
            ]
        ]);
    }
    public function getGpaChartData()
{
    $studentId = Auth::id();

    // Học kỳ đang hoạt động
    $semesterId = DB::table('semester')
        ->where('status_id', 1)
        ->value('semester_id');

    $rows = DB::table('student_score')
        ->join('enrollment', 'student_score.enrollment_id', '=', 'enrollment.enrollment_id')
        ->join('class_section', 'enrollment.class_section_id', '=', 'class_section.class_section_id')
        ->join('course', 'class_section.course_id', '=', 'course.course_id')
        ->where('enrollment.student_id', $studentId)
        ->where('class_section.semester_id', $semesterId)
        ->groupBy('course.course_name')
        ->select(
            'course.course_name as course_name',
            DB::raw('AVG(student_score.score_value) as gpa')
        )
        ->get();

    return response()->json([
        'labels' => $rows->pluck('course_name'),
        'values' => $rows->pluck('gpa')->map(fn ($v) => round($v, 2))
    ]);
}
}
