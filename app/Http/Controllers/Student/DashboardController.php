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
         * 1️⃣ GPA TÍCH LŨY (THANG 4)
         * =============================== */
        // Lấy tất cả điểm của sinh viên
        $scores = DB::table('student_score as ss')
            ->join('enrollment as e', 'ss.enrollment_id', '=', 'e.enrollment_id')
            ->join('class_section as cs', 'e.class_section_id', '=', 'cs.class_section_id')
            ->join('course_version as cv', 'cs.course_version_id', '=', 'cv.course_version_id')
            ->where('e.student_id', $studentId)
            ->whereNotNull('ss.score_value')
            ->select('ss.score_value', 'cv.credit')
            ->get();

        $gpaTotal = $this->calculateGPA4($scores);

        /* ===============================
         * 2️⃣ GPA HỌC KỲ MỚI NHẤT (THANG 4)
         * =============================== */
        $currentSemesterId = DB::table('semester')
            ->orderByDesc('semester_id')
            ->value('semester_id');

        $semesterScores = DB::table('student_score as ss')
            ->join('enrollment as e', 'ss.enrollment_id', '=', 'e.enrollment_id')
            ->join('class_section as cs', 'e.class_section_id', '=', 'cs.class_section_id')
            ->join('course_version as cv', 'cs.course_version_id', '=', 'cv.course_version_id')
            ->where('e.student_id', $studentId)
            ->where('cs.semester_id', $currentSemesterId)
            ->whereNotNull('ss.score_value')
            ->select('ss.score_value', 'cv.credit')
            ->get();

        $gpaSemester = $this->calculateGPA4($semesterScores);

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
         * ⚠️ 7️⃣ CẢNH BÁO HỌC VỤ (CẬP NHẬT THEO THANG 4)
         * =============================== */
        if ($gpaTotal !== null && $gpaTotal < 2.0) {
            $notifications[] = [
                'type' => 'warning',
                'title' => 'Cảnh báo học vụ',
                'message' => 'GPA hiện tại dưới 2.0/4.0, vui lòng chú ý kết quả học tập'
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

    /* ===============================
     * 🔢 HÀM QUY ĐỔI ĐIỂM 10 → 4
     * =============================== */
    private function convertScoreTo4Scale($score)
    {
        if ($score >= 9.5) return 4.0;
        if ($score >= 8.5) return 3.7;
        if ($score >= 8.0) return 3.5;
        if ($score >= 7.0) return 3.0;
        if ($score >= 6.5) return 2.5;
        if ($score >= 5.5) return 2.0;
        if ($score >= 5.0) return 1.5;
        if ($score >= 4.0) return 1.0;
        return 0.0;
    }

    /* ===============================
     * 📊 HÀM TÍNH GPA THANG 4 (CÓ TRỌNG SỐ TÍN CHỈ)
     * =============================== */
    private function calculateGPA4($scores)
    {
        if ($scores->isEmpty()) {
            return null;
        }

        $totalWeightedGrade = 0;
        $totalCredits = 0;

        foreach ($scores as $score) {
            $grade4 = $this->convertScoreTo4Scale($score->score_value);
            $credit = $score->credit;

            $totalWeightedGrade += ($grade4 * $credit);
            $totalCredits += $credit;
        }

        if ($totalCredits == 0) {
            return null;
        }

        return round($totalWeightedGrade / $totalCredits, 2);
    }

    /* ===============================
     * 📈 HÀM LẤY XẾP LOẠI (TÙY CHỌN)
     * =============================== */
    private function getGradeClassification($gpa)
    {
        if ($gpa === null) return 'Chưa có';
        if ($gpa >= 3.6) return 'Xuất sắc';
        if ($gpa >= 3.2) return 'Giỏi';
        if ($gpa >= 2.5) return 'Khá';
        if ($gpa >= 2.0) return 'Trung bình';
        return 'Yếu';
    }
}
