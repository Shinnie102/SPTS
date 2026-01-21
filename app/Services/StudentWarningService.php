<?php

namespace App\Services;

use App\Models\User;
use App\Models\Enrollment;
use App\Models\Attendance;
use App\Models\StudentScore;
use App\Models\ClassSection;
use App\Models\AcademicRule;
use Illuminate\Support\Facades\DB;

/**
 * Class StudentWarningService
 * 
 * Service xử lý logic cảnh báo học vụ cho sinh viên
 * Phân tích dữ liệu điểm danh, điểm số, GPA để đưa ra cảnh báo
 */
class StudentWarningService
{
    /**
     * Ngưỡng cảnh báo từ academic_rule table
     */
    private $minGPA;
    private $minAttendanceRate;
    private $maxFailedCourses;
    private $graduationGPA;

    public function __construct()
    {
        // Load rules from database
        $this->loadAcademicRules();
    }

    /**
     * Load academic rules from database
     */
    private function loadAcademicRules()
    {
        $rules = AcademicRule::where('status_id', 1)->get()->keyBy('rule_type');
        
        $this->minGPA = $rules->get('MIN_GPA')->threshold_value ?? 1.5;
        $this->minAttendanceRate = $rules->get('MIN_ATTENDANCE')->threshold_value ?? 80;
        $this->maxFailedCourses = $rules->get('MAX_FAILED_COURSES')->threshold_value ?? 3;
        $this->graduationGPA = $rules->get('GRADUATION_GPA')->threshold_value ?? 2.0;
    }

    /**
     * Lấy tất cả cảnh báo cho sinh viên
     *
     * @param int $studentId
     * @return array
     */
    public function getStudentWarnings(int $studentId): array
    {
        $warnings = [];
        
        // Lấy học kỳ hiện tại
        $currentSemester = DB::table('semester')
            ->where('status_id', 1)
            ->first();

        if (!$currentSemester) {
            return [
                'hasViolations' => false,
                'warnings' => []
            ];
        }

        // Lấy danh sách lớp đang học của sinh viên
        $activeEnrollments = $this->getActiveEnrollments($studentId, $currentSemester->semester_id);

        // Tính GPA học kỳ một lần (để tránh duplicate)
        $semesterGPA = $this->calculateSemesterGPA($studentId, $currentSemester->semester_id);
        
        // Tính GPA tổng tích lũy (cumulative GPA)
        $cumulativeGPA = $this->calculateCumulativeGPA($studentId);

        // 1. Kiểm tra cảnh báo GPA tổng tích lũy
        $cumulativeGPAWarning = $this->checkCumulativeGPAWarning($cumulativeGPA);
        if ($cumulativeGPAWarning) {
            $warnings[] = $cumulativeGPAWarning;
        }

        // 2. Kiểm tra cảnh báo GPA học kỳ
        $semesterGPAWarning = $this->checkSemesterGPAWarning($semesterGPA);
        if ($semesterGPAWarning) {
            $warnings[] = $semesterGPAWarning;
        }

        // 3. Kiểm tra cảnh báo chuyên cần
        $attendanceWarning = $this->checkAttendanceWarning($studentId, $activeEnrollments);
        if ($attendanceWarning) {
            $warnings[] = $attendanceWarning;
        }

        // 4. Kiểm tra môn học rớt
        $failedCoursesWarning = $this->checkFailedCourses($studentId);
        if ($failedCoursesWarning) {
            $warnings[] = $failedCoursesWarning;
        }

        // 5. Kiểm tra cảnh báo học vụ tổng hợp (GPA + chuyên cần)
        $compositeWarning = $this->checkCompositeWarning($semesterGPA, $activeEnrollments);
        if ($compositeWarning) {
            $warnings[] = $compositeWarning;
        }

        return [
            'hasViolations' => !empty($warnings),
            'warnings' => $warnings
        ];
    }

    /**
     * Lấy danh sách enrollment đang active trong học kỳ
     *
     * @param int $studentId
     * @param int $semesterId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getActiveEnrollments(int $studentId, int $semesterId)
    {
        return Enrollment::where('student_id', $studentId)
            ->where('enrollment_status_id', 1) // 1 = ACTIVE
            ->whereHas('classSection', function($query) use ($semesterId) {
                $query->where('semester_id', $semesterId);
            })
            ->with([
                'classSection.courseVersion.course',
                'classSection.semester'
            ])
            ->get();
    }

    /**
     * Tính GPA học kỳ
     */
    private function calculateSemesterGPA(int $studentId, int $semesterId): ?float
    {
        $gpa = DB::table('student_score')
            ->join('enrollment', 'student_score.enrollment_id', '=', 'enrollment.enrollment_id')
            ->join('class_section', 'enrollment.class_section_id', '=', 'class_section.class_section_id')
            ->where('enrollment.student_id', $studentId)
            ->where('class_section.semester_id', $semesterId)
            ->whereNotNull('student_score.score_value')
            ->avg('student_score.score_value');

        return $gpa;
    }

    /**
     * Tính GPA tổng tích lũy (cumulative GPA) của tất cả các học kỳ đã hoàn thành
     */
    private function calculateCumulativeGPA(int $studentId): ?float
    {
        $gpa = DB::table('student_score')
            ->join('enrollment', 'student_score.enrollment_id', '=', 'enrollment.enrollment_id')
            ->where('enrollment.student_id', $studentId)
            ->where('enrollment.enrollment_status_id', 2) // COMPLETED enrollments only
            ->whereNotNull('student_score.score_value')
            ->avg('student_score.score_value');

        return $gpa;
    }

    /**
     * Kiểm tra cảnh báo GPA tổng tích lũy
     */
    private function checkCumulativeGPAWarning(?float $cumulativeGPA): ?array
    {
        if ($cumulativeGPA === null || $cumulativeGPA >= $this->minGPA) {
            return null;
        }

        $severity = $cumulativeGPA < 1.0 ? 'high' : 'medium';

        return [
            'type' => 'cumulative_gpa_warning',
            'title' => 'Cảnh báo học vụ (GPA Tích lũy)',
            'message' => sprintf(
                'GPA tích lũy của bạn là %.2f, thấp hơn mức tối thiểu %.2f. Bạn có nguy cơ bị đình chỉ học nếu không cải thiện kết quả học tập.',
                $cumulativeGPA,
                $this->minGPA
            ),
            'severity' => $severity,
            'details' => [
                ['label' => 'GPA tích lũy hiện tại', 'value' => number_format($cumulativeGPA, 2)],
                ['label' => 'GPA tối thiểu yêu cầu', 'value' => number_format($this->minGPA, 2)],
                ['label' => 'GPA tốt nghiệp yêu cầu', 'value' => number_format($this->graduationGPA, 2)]
            ]
        ];
    }

    /**
     * Kiểm tra cảnh báo GPA học kỳ
     */
    private function checkSemesterGPAWarning(?float $semesterGPA): ?array
    {
        if ($semesterGPA === null || $semesterGPA >= $this->minGPA) {
            return null;
        }

        $severity = $semesterGPA < 1.0 ? 'high' : 'medium';

        return [
            'type' => 'semester_gpa_warning',
            'title' => 'Cảnh báo học vụ (GPA Học kỳ)',
            'message' => sprintf(
                'GPA học kỳ của bạn là %.2f, thấp hơn mức tối thiểu %.2f. Bạn cần cải thiện kết quả học tập để không bị cảnh báo học vụ.',
                $semesterGPA,
                $this->minGPA
            ),
            'severity' => $severity,
            'details' => [
                ['label' => 'GPA học kỳ hiện tại', 'value' => number_format($semesterGPA, 2)],
                ['label' => 'GPA tối thiểu yêu cầu', 'value' => number_format($this->minGPA, 2)]
            ]
        ];
    }

    /**
     * Kiểm tra cảnh báo chuyên cần
     */
    private function checkAttendanceWarning(int $studentId, $enrollments): ?array
    {
        $lowAttendanceSubjects = [];

        foreach ($enrollments as $enrollment) {
            $attendanceRate = $this->calculateAttendanceRate($enrollment->enrollment_id);
            
            // Nếu tỷ lệ chuyên cần dưới ngưỡng
            if ($attendanceRate < $this->minAttendanceRate) {
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

        $minRate = min(array_column(
            array_map(fn($s) => ['rate' => floatval(rtrim($s['rate'], '%'))], $lowAttendanceSubjects),
            'rate'
        ));
        
        $severity = $minRate < 70 ? 'high' : 'medium';

        return [
            'type' => 'attendance',
            'title' => 'Cảnh báo học vụ (Chuyên cần)',
            'message' => sprintf(
                'Một hoặc nhiều môn học của bạn có tỷ lệ chuyên cần thấp hơn %.0f%%. Hãy chú ý điểm danh đầy đủ để tránh bị cấm thi.',
                $this->minAttendanceRate
            ),
            'severity' => $severity,
            'subjects' => $lowAttendanceSubjects
        ];
    }

    /**
     * Kiểm tra môn học rớt
     */
    private function checkFailedCourses(int $studentId): ?array
    {
        // Lấy các môn đã hoàn thành với điểm trung bình < 5.0
        $failedCourses = DB::table('enrollment')
            ->join('class_section', 'enrollment.class_section_id', '=', 'class_section.class_section_id')
            ->join('course_version', 'class_section.course_version_id', '=', 'course_version.course_version_id')
            ->join('course', 'course_version.course_id', '=', 'course.course_id')
            ->leftJoin('student_score', 'enrollment.enrollment_id', '=', 'student_score.enrollment_id')
            ->where('enrollment.student_id', $studentId)
            ->where('enrollment.enrollment_status_id', 2) // COMPLETED
            ->groupBy('enrollment.enrollment_id', 'course.course_name')
            ->havingRaw('AVG(student_score.score_value) < 5.0 OR AVG(student_score.score_value) IS NULL')
            ->select(
                'course.course_name',
                DB::raw('COALESCE(AVG(student_score.score_value), 0) as avg_score')
            )
            ->get();

        if ($failedCourses->isEmpty()) {
            return null;
        }

        $subjects = $failedCourses->map(function($course) {
            return [
                'name' => $course->course_name,
                'status' => 'Rớt - Cần học lại/cải thiện',
                'score' => number_format($course->avg_score, 1)
            ];
        })->toArray();

        $severity = count($subjects) >= $this->maxFailedCourses ? 'high' : 'medium';

        return [
            'type' => 'failed_courses',
            'title' => 'Môn học rớt',
            'message' => sprintf(
                'Bạn có %d môn học bị rớt. Hãy đăng ký học lại hoặc học cải thiện các môn này.',
                count($subjects)
            ),
            'severity' => $severity,
            'subjects' => $subjects
        ];
    }

    /**
     * Kiểm tra cảnh báo học vụ tổng hợp (GPA và chuyên cần đều không đạt)
     */
    private function checkCompositeWarning(?float $semesterGPA, $enrollments): ?array
    {
        // Kiểm tra xem có môn nào dưới ngưỡng chuyên cần không
        $hasLowAttendance = false;
        foreach ($enrollments as $enrollment) {
            $attendanceRate = $this->calculateAttendanceRate($enrollment->enrollment_id);
            if ($attendanceRate < $this->minAttendanceRate) {
                $hasLowAttendance = true;
                break;
            }
        }

        // Nếu cả GPA và chuyên cần đều không đạt
        if ($semesterGPA !== null && $semesterGPA < $this->minGPA && $hasLowAttendance) {
            return [
                'type' => 'composite_warning',
                'title' => 'Cảnh báo học vụ mức cao',
                'message' => sprintf(
                    'Bạn đang có cả GPA học kỳ (%.2f) và tỷ lệ chuyên cần dưới mức yêu cầu. Đây là cảnh báo nghiêm trọng, vui lòng liên hệ cố vấn học tập ngay.',
                    $semesterGPA
                ),
                'severity' => 'high',
                'details' => [
                    ['label' => 'GPA học kỳ', 'value' => number_format($semesterGPA, 2) . ' (yêu cầu: ' . number_format($this->minGPA, 2) . ')'],
                    ['label' => 'Chuyên cần', 'value' => 'Dưới ' . number_format($this->minAttendanceRate, 0) . '%']
                ]
            ];
        }

        return null;
    }

    /**
     * Tính tỷ lệ điểm danh (%)
     */
    private function calculateAttendanceRate(int $enrollmentId): float
    {
        $totalClasses = Attendance::where('enrollment_id', $enrollmentId)->count();
        
        if ($totalClasses === 0) {
            return 100.0;
        }

        $presentCount = Attendance::where('enrollment_id', $enrollmentId)
            ->where('attendance_status_id', 1)
            ->count();

        return ($presentCount / $totalClasses) * 100;
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
            return null;
        }

        $totalScore = $scores->sum('score_value');
        $count = $scores->count();

        return $totalScore / $count;
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
        if ($attendanceRate < 70) {
            return 'Nguy cơ cấm thi';
        }

        if ($avgScore !== null && $avgScore < 4.0) {
            return 'Nguy cơ không đạt';
        }

        if ($attendanceRate < $this->minAttendanceRate || 
            ($avgScore !== null && $avgScore < 5.0)) {
            return 'Cần cải thiện';
        }

        return 'Bình thường';
    }
}
