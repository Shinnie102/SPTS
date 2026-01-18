<?php

namespace App\Services;

use App\Models\User;
use App\Models\Enrollment;
use App\Models\Attendance;
use App\Models\StudentScore;
use App\Models\ClassSection;
use Illuminate\Support\Facades\DB;

/**
 * Class StudentWarningService
 * 
 * Service xử lý logic cảnh báo học vụ cho sinh viên
 * Phân tích dữ liệu điểm danh, điểm số để đưa ra cảnh báo
 */
class StudentWarningService
{
    /**
     * Ngưỡng cảnh báo (có thể lấy từ academic_rule table)
     */
    private const MIN_ATTENDANCE_RATE = 80; // % - Dưới mức này sẽ bị cảnh báo
    private const CRITICAL_ATTENDANCE_RATE = 70; // % - Nguy cơ cấm thi
    private const LOW_SCORE_THRESHOLD = 5.0; // Điểm - Dưới mức này coi là thấp
    private const FAIL_RISK_THRESHOLD = 4.0; // Điểm - Nguy cơ không đạt
    private const MIN_PASSING_SCORE = 5.0; // Điểm đạt tối thiểu

    /**
     * Lấy tất cả cảnh báo cho sinh viên
     *
     * @param int $studentId
     * @return array
     */
    public function getStudentWarnings(int $studentId): array
    {
        $warnings = [];
        
        // Lấy danh sách lớp đang học của sinh viên (status = 1: ACTIVE)
        $activeEnrollments = $this->getActiveEnrollments($studentId);
        
        if ($activeEnrollments->isEmpty()) {
            return [
                'hasViolations' => false,
                'warnings' => []
            ];
        }

        // 1. Kiểm tra cảnh báo chuyên cần
        $attendanceWarning = $this->checkAttendanceWarning($studentId, $activeEnrollments);
        if ($attendanceWarning) {
            $warnings[] = $attendanceWarning;
        }

        // 2. Kiểm tra cảnh báo điểm số thấp
        $lowScoreWarning = $this->checkLowScoreWarning($studentId, $activeEnrollments);
        if ($lowScoreWarning) {
            $warnings[] = $lowScoreWarning;
        }

        // 3. Kiểm tra cảnh báo nguy cơ không đạt
        $failRiskWarning = $this->checkFailRiskWarning($studentId, $activeEnrollments);
        if ($failRiskWarning) {
            $warnings[] = $failRiskWarning;
        }

        return [
            'hasViolations' => !empty($warnings),
            'warnings' => $warnings
        ];
    }

    /**
     * Lấy danh sách enrollment đang active
     *
     * @param int $studentId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getActiveEnrollments(int $studentId)
    {
        return Enrollment::where('student_id', $studentId)
            ->where('enrollment_status_id', 1) // 1 = ACTIVE
            ->with([
                'classSection.courseVersion.course',
                'classSection.semester'
            ])
            ->get();
    }

    /**
     * Kiểm tra cảnh báo chuyên cần
     *
     * @param int $studentId
     * @param \Illuminate\Database\Eloquent\Collection $enrollments
     * @return array|null
     */
    private function checkAttendanceWarning(int $studentId, $enrollments): ?array
    {
        $lowAttendanceSubjects = [];

        foreach ($enrollments as $enrollment) {
            $attendanceRate = $this->calculateAttendanceRate($enrollment->enrollment_id);
            
            // Nếu tỷ lệ chuyên cần dưới ngưỡng
            if ($attendanceRate < self::MIN_ATTENDANCE_RATE) {
                $courseName = $enrollment->classSection->courseVersion->course->course_name ?? 'N/A';
                
                $lowAttendanceSubjects[] = [
                    'name' => $courseName,
                    'rate' => number_format($attendanceRate, 1) . '%',
                    'attendance_rate' => $attendanceRate
                ];
            }
        }

        if (empty($lowAttendanceSubjects)) {
            return null;
        }

        // Sắp xếp theo tỷ lệ chuyên cần từ thấp đến cao
        usort($lowAttendanceSubjects, function($a, $b) {
            return $a['attendance_rate'] <=> $b['attendance_rate'];
        });

        // Remove temporary field
        foreach ($lowAttendanceSubjects as &$subject) {
            unset($subject['attendance_rate']);
        }

        // Xác định mức độ nghiêm trọng
        $minRate = min(array_column(
            array_map(fn($s) => ['rate' => floatval(rtrim($s['rate'], '%'))], $lowAttendanceSubjects),
            'rate'
        ));
        
        $severity = $minRate < self::CRITICAL_ATTENDANCE_RATE ? 'high' : 'medium';

        return [
            'type' => 'attendance',
            'title' => 'Cảnh báo chuyên cần',
            'message' => 'Một hoặc nhiều môn học của bạn đang có tỷ lệ chuyên cần thấp hơn mức cho phép (dưới ' 
                        . self::MIN_ATTENDANCE_RATE . '%). Hãy chú ý điểm danh đầy đủ để tránh bị cấm thi.',
            'severity' => $severity,
            'subjects' => $lowAttendanceSubjects
        ];
    }

    /**
     * Tính tỷ lệ điểm danh (%)
     *
     * @param int $enrollmentId
     * @return float
     */
    private function calculateAttendanceRate(int $enrollmentId): float
    {
        // Đếm tổng số buổi học
        $totalClasses = Attendance::where('enrollment_id', $enrollmentId)->count();
        
        if ($totalClasses === 0) {
            return 100.0; // Chưa có buổi học nào
        }

        // Đếm số buổi có mặt (status_id = 1: PRESENT)
        $presentCount = Attendance::where('enrollment_id', $enrollmentId)
            ->where('attendance_status_id', 1)
            ->count();

        return ($presentCount / $totalClasses) * 100;
    }

    /**
     * Kiểm tra cảnh báo điểm số thấp
     *
     * @param int $studentId
     * @param \Illuminate\Database\Eloquent\Collection $enrollments
     * @return array|null
     */
    private function checkLowScoreWarning(int $studentId, $enrollments): ?array
    {
        $lowScoreSubjects = [];

        foreach ($enrollments as $enrollment) {
            // Lấy điểm trung bình các thành phần (chưa có điểm cuối kỳ)
            $avgScore = $this->calculateAverageScore($enrollment->enrollment_id);
            
            if ($avgScore !== null && $avgScore < self::LOW_SCORE_THRESHOLD && $avgScore >= self::FAIL_RISK_THRESHOLD) {
                $courseName = $enrollment->classSection->courseVersion->course->course_name ?? 'N/A';
                
                $lowScoreSubjects[] = [
                    'name' => $courseName,
                    'score' => number_format($avgScore, 1),
                    'avg_score' => $avgScore
                ];
            }
        }

        if (empty($lowScoreSubjects)) {
            return null;
        }

        // Sắp xếp theo điểm từ thấp đến cao
        usort($lowScoreSubjects, function($a, $b) {
            return $a['avg_score'] <=> $b['avg_score'];
        });

        // Remove temporary field
        foreach ($lowScoreSubjects as &$subject) {
            unset($subject['avg_score']);
        }

        return [
            'type' => 'low_score',
            'title' => 'Cảnh báo điểm số thấp',
            'message' => 'Một số môn học của bạn có điểm kiểm tra dưới mức trung bình (dưới ' 
                        . self::LOW_SCORE_THRESHOLD . ' điểm). Hãy tập trung học tập và cải thiện điểm số.',
            'severity' => 'medium',
            'subjects' => $lowScoreSubjects
        ];
    }

    /**
     * Kiểm tra cảnh báo nguy cơ không đạt
     *
     * @param int $studentId
     * @param \Illuminate\Database\Eloquent\Collection $enrollments
     * @return array|null
     */
    private function checkFailRiskWarning(int $studentId, $enrollments): ?array
    {
        $failRiskSubjects = [];

        foreach ($enrollments as $enrollment) {
            $avgScore = $this->calculateAverageScore($enrollment->enrollment_id);
            
            // Nguy cơ không đạt: điểm dưới 4.0
            if ($avgScore !== null && $avgScore < self::FAIL_RISK_THRESHOLD) {
                $courseName = $enrollment->classSection->courseVersion->course->course_name ?? 'N/A';
                
                $failRiskSubjects[] = [
                    'name' => $courseName,
                    'status' => 'Điểm thường xuyên: ' . number_format($avgScore, 1),
                    'avg_score' => $avgScore
                ];
            }
        }

        if (empty($failRiskSubjects)) {
            return null;
        }

        // Sắp xếp theo điểm từ thấp đến cao
        usort($failRiskSubjects, function($a, $b) {
            return $a['avg_score'] <=> $b['avg_score'];
        });

        // Remove temporary field
        foreach ($failRiskSubjects as &$subject) {
            unset($subject['avg_score']);
        }

        return [
            'type' => 'fail_risk',
            'title' => 'Cảnh báo nguy cơ không đạt',
            'message' => 'Các môn học dưới đây có nguy cơ không đủ điểm đạt (dưới ' 
                        . self::FAIL_RISK_THRESHOLD . ' điểm). Vui lòng liên hệ giảng viên hoặc tham gia học bù.',
            'severity' => 'high',
            'subjects' => $failRiskSubjects
        ];
    }

    /**
     * Tính điểm trung bình các thành phần
     *
     * @param int $enrollmentId
     * @return float|null
     */
    private function calculateAverageScore(int $enrollmentId): ?float
    {
        $scores = StudentScore::where('enrollment_id', $enrollmentId)
            ->whereNotNull('score_value')
            ->get();

        if ($scores->isEmpty()) {
            return null; // Chưa có điểm
        }

        $totalScore = $scores->sum('score_value');
        $count = $scores->count();

        return $totalScore / $count;
    }

    /**
     * Lấy chi tiết cảnh báo cho một môn học cụ thể
     *
     * @param int $studentId
     * @param int $classSectionId
     * @return array
     */
    public function getSubjectWarningDetail(int $studentId, int $classSectionId): array
    {
        $enrollment = Enrollment::where('student_id', $studentId)
            ->where('class_section_id', $classSectionId)
            ->with('classSection.courseVersion.course')
            ->first();

        if (!$enrollment) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy thông tin đăng ký môn học'
            ];
        }

        $attendanceRate = $this->calculateAttendanceRate($enrollment->enrollment_id);
        $avgScore = $this->calculateAverageScore($enrollment->enrollment_id);

        return [
            'success' => true,
            'data' => [
                'course_name' => $enrollment->classSection->courseVersion->course->course_name,
                'attendance_rate' => number_format($attendanceRate, 1),
                'average_score' => $avgScore ? number_format($avgScore, 1) : 'Chưa có điểm',
                'status' => $this->getSubjectStatus($attendanceRate, $avgScore)
            ]
        ];
    }

    /**
     * Xác định trạng thái môn học
     *
     * @param float $attendanceRate
     * @param float|null $avgScore
     * @return string
     */
    private function getSubjectStatus(float $attendanceRate, ?float $avgScore): string
    {
        if ($attendanceRate < self::CRITICAL_ATTENDANCE_RATE) {
            return 'Nguy cơ cấm thi';
        }

        if ($avgScore !== null && $avgScore < self::FAIL_RISK_THRESHOLD) {
            return 'Nguy cơ không đạt';
        }

        if ($attendanceRate < self::MIN_ATTENDANCE_RATE || 
            ($avgScore !== null && $avgScore < self::LOW_SCORE_THRESHOLD)) {
            return 'Cần cải thiện';
        }

        return 'Bình thường';
    }

    /**
     * Lấy thống kê tổng quan
     *
     * @param int $studentId
     * @return array
     */
    public function getWarningStatistics(int $studentId): array
    {
        $activeEnrollments = $this->getActiveEnrollments($studentId);
        
        $stats = [
            'total_subjects' => $activeEnrollments->count(),
            'low_attendance_count' => 0,
            'low_score_count' => 0,
            'fail_risk_count' => 0,
            'overall_status' => 'good'
        ];

        foreach ($activeEnrollments as $enrollment) {
            $attendanceRate = $this->calculateAttendanceRate($enrollment->enrollment_id);
            $avgScore = $this->calculateAverageScore($enrollment->enrollment_id);

            if ($attendanceRate < self::MIN_ATTENDANCE_RATE) {
                $stats['low_attendance_count']++;
            }

            if ($avgScore !== null) {
                if ($avgScore < self::FAIL_RISK_THRESHOLD) {
                    $stats['fail_risk_count']++;
                } elseif ($avgScore < self::LOW_SCORE_THRESHOLD) {
                    $stats['low_score_count']++;
                }
            }
        }

        // Xác định trạng thái tổng quan
        if ($stats['fail_risk_count'] > 0 || $stats['low_attendance_count'] > 2) {
            $stats['overall_status'] = 'critical';
        } elseif ($stats['low_score_count'] > 0 || $stats['low_attendance_count'] > 0) {
            $stats['overall_status'] = 'warning';
        }

        return $stats;
    }
}
