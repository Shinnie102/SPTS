<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $studentId = Auth::id();
        $notifications = [];

        /* ===============================
         * 1️⃣ GPA TÍCH LŨY
         * =============================== */
        $gpaTotal = DB::table('student_score as ss')
            ->join('enrollment as e', 'ss.enrollment_id', '=', 'e.enrollment_id')
            ->where('e.student_id', $studentId)
            ->avg('ss.score_value');

        $gpaTotal = $gpaTotal ? round($gpaTotal, 2) : null;

        /* ===============================
         * 2️⃣ GPA HỌC KỲ MỚI NHẤT
         * =============================== */
        $currentSemesterId = DB::table('semester')
            ->orderByDesc('semester_id')
            ->value('semester_id');

        $gpaSemester = DB::table('student_score as ss')
            ->join('enrollment as e', 'ss.enrollment_id', '=', 'e.enrollment_id')
            ->join('class_section as cs', 'e.class_section_id', '=', 'cs.class_section_id')
            ->where('e.student_id', $studentId)
            ->where('cs.semester_id', $currentSemesterId)
            ->avg('ss.score_value');

        $gpaSemester = $gpaSemester ? round($gpaSemester, 2) : null;

        /* ===============================
         * 3️⃣ TÍN CHỈ TÍCH LŨY
         * =============================== */
        $totalCredits = DB::table('enrollment as e')
            ->join('class_section as cs', 'e.class_section_id', '=', 'cs.class_section_id')
            ->join('course_version as cv', 'cs.course_version_id', '=', 'cv.course_version_id')
            ->where('e.student_id', $studentId)
            ->sum('cv.credit');

        $totalCredits = $totalCredits ?? 0;

        /* ===============================
         * 4️⃣ TÍN CHỈ CÒN LẠI
         * =============================== */
        $requiredCredits = 120;
        $remainingCredits = max($requiredCredits - $totalCredits, 0);

        /* ===============================
         * 5️⃣ TỶ LỆ CHUYÊN CẦN
         * =============================== */
        $attendanceRate = DB::table('attendance as a')
            ->join('enrollment as e', 'a.enrollment_id', '=', 'e.enrollment_id')
            ->join('attendance_status as s', 'a.attendance_status_id', '=', 's.status_id')
            ->where('e.student_id', $studentId)
            ->selectRaw("
                ROUND(
                    SUM(
                        CASE
                            WHEN s.code IN ('PRESENT','LATE','EXCUSED') THEN 1
                            ELSE 0
                        END
                    ) / NULLIF(COUNT(*), 0) * 100,
                0) as rate
            ")
            ->value('rate');

        $attendanceRate = $attendanceRate ?? 0;

        /* ===============================
         * 🔔 6️⃣ THÔNG BÁO – CÓ ĐIỂM MỚI
         * =============================== */
        $newScoreCount = DB::table('student_score as ss')
            ->join('enrollment as e', 'ss.enrollment_id', '=', 'e.enrollment_id')
            ->where('e.student_id', $studentId)
            ->whereNotNull('ss.score_value')
            ->whereDate('ss.last_updated_at', '>=', Carbon::now()->subDays(7))
            ->count();

        if ($newScoreCount > 0) {
            $notifications[] = [
                'type' => 'success',
                'title' => 'Có điểm mới được công bố',
                'message' => "Có {$newScoreCount} môn học vừa được cập nhật điểm"
            ];
        }

        /* ===============================
         * ⚠️ 7️⃣ CẢNH BÁO HỌC VỤ
         * =============================== */
        if ($gpaTotal !== null && $gpaTotal < 2.0) {
            $notifications[] = [
                'type' => 'warning',
                'title' => 'Cảnh báo học vụ',
                'message' => 'GPA hiện tại dưới 2.0, vui lòng chú ý kết quả học tập'
            ];
        }

        /* ===============================
         * 🚫 8️⃣ NGHỈ HỌC QUÁ SỐ BUỔI
         * =============================== */
        $absentCount = DB::table('attendance as a')
            ->join('enrollment as e', 'a.enrollment_id', '=', 'e.enrollment_id')
            ->join('attendance_status as s', 'a.attendance_status_id', '=', 's.status_id')
            ->where('e.student_id', $studentId)
            ->where('s.code', 'ABSENT')
            ->count();

        if ($absentCount >= 5) {
            $notifications[] = [
                'type' => 'danger',
                'title' => 'Nghỉ học quá số buổi cho phép',
                'message' => "Bạn đã nghỉ {$absentCount} buổi – có nguy cơ cấm thi"
            ];
        }

        /* ===============================
         * 9️⃣ TRẢ VIEW
         * =============================== */
        return view('student.studentDashboard', compact(
            'gpaTotal',
            'gpaSemester',
            'totalCredits',
            'remainingCredits',
            'attendanceRate',
            'notifications'
        ));
    }
}
