<?php

namespace App\Services\Student\Warning;

use App\Contracts\EnrollmentRepositoryInterface;
use App\Contracts\SemesterRepositoryInterface;
use App\Contracts\AcademicRuleRepositoryInterface;

/**
 * Class StudentWarningService
 * Service xử lý logic cảnh báo học tập của sinh viên
 * - Lấy dữ liệu từ Repositories
 * - Điều phối các warning checkers
 * - Tổng hợp kết quả warnings
 */
class StudentWarningService
{
    /**
     * Status constants
     */
    private const STATUS_ACTIVE = 1;
    private const STATUS_COMPLETED = 2;

    /**
     * Default thresholds (fallback nếu không load được từ DB)
     */
    private const DEFAULT_MIN_GPA = 1.5;
    private const DEFAULT_MIN_ATTENDANCE = 80;
    private const DEFAULT_MAX_FAILED_COURSES = 3;
    private const DEFAULT_GRADUATION_GPA = 2.0;

    /**
     * Ngưỡng cảnh báo từ academic_rule table
     */
    private float $minGPA;
    private float $minAttendanceRate;
    private int $maxFailedCourses;
    private float $graduationGPA;

    /**
     * @var EnrollmentRepositoryInterface
     */
    protected $enrollmentRepository;

    /**
     * @var SemesterRepositoryInterface
     */
    protected $semesterRepository;

    /**
     * @var AcademicRuleRepositoryInterface
     */
    protected $academicRuleRepository;

    /**
     * @var GPAWarningChecker
     */
    protected $gpaChecker;

    /**
     * @var AttendanceWarningChecker
     */
    protected $attendanceChecker;

    /**
     * @var FailedCourseWarningChecker
     */
    protected $failedCourseChecker;

    /**
     * Constructor - Dependency Injection
     * 
     * @param EnrollmentRepositoryInterface $enrollmentRepository
     * @param SemesterRepositoryInterface $semesterRepository
     * @param AcademicRuleRepositoryInterface $academicRuleRepository
     * @param GPAWarningChecker $gpaChecker
     * @param AttendanceWarningChecker $attendanceChecker
     * @param FailedCourseWarningChecker $failedCourseChecker
     */
    public function __construct(
        EnrollmentRepositoryInterface $enrollmentRepository,
        SemesterRepositoryInterface $semesterRepository,
        AcademicRuleRepositoryInterface $academicRuleRepository,
        GPAWarningChecker $gpaChecker,
        AttendanceWarningChecker $attendanceChecker,
        FailedCourseWarningChecker $failedCourseChecker
    ) {
        $this->enrollmentRepository = $enrollmentRepository;
        $this->semesterRepository = $semesterRepository;
        $this->academicRuleRepository = $academicRuleRepository;
        $this->gpaChecker = $gpaChecker;
        $this->attendanceChecker = $attendanceChecker;
        $this->failedCourseChecker = $failedCourseChecker;
        
        $this->loadAcademicRules();
    }

    /**
     * Load academic rules from database
     * 
     * Sử dụng AcademicRuleRepository thay vì query trực tiếp
     * Sử dụng default constants nếu không tìm thấy trong DB
     * 
     * @return void
     */
    private function loadAcademicRules(): void
    {
        try {
            // Lấy tất cả active rules dưới dạng key-value
            $rules = $this->academicRuleRepository->getActiveRulesKeyValue();
            
            // Load với fallback to defaults
            $this->minGPA = $rules['MIN_GPA'] ?? self::DEFAULT_MIN_GPA;
            $this->minAttendanceRate = $rules['MIN_ATTENDANCE'] ?? self::DEFAULT_MIN_ATTENDANCE;
            $this->maxFailedCourses = (int)($rules['MAX_FAILED_COURSES'] ?? self::DEFAULT_MAX_FAILED_COURSES);
            $this->graduationGPA = $rules['GRADUATION_GPA'] ?? self::DEFAULT_GRADUATION_GPA;
        } catch (\Exception $e) {
            // Fallback to default values nếu có lỗi khi query DB
            \Log::warning('Failed to load academic rules, using defaults: ' . $e->getMessage());
            
            $this->minGPA = self::DEFAULT_MIN_GPA;
            $this->minAttendanceRate = self::DEFAULT_MIN_ATTENDANCE;
            $this->maxFailedCourses = self::DEFAULT_MAX_FAILED_COURSES;
            $this->graduationGPA = self::DEFAULT_GRADUATION_GPA;
        }
    }

    /**
     * Lấy tất cả cảnh báo cho sinh viên
     * 
     * Orchestrate các warning checkers
     *
     * @param int $studentId
     * @return array
     */
    public function getStudentWarnings(int $studentId): array
    {
        $warnings = [];
        
        // Lấy học kỳ hiện tại từ SemesterRepository
        $currentSemester = $this->semesterRepository->getActiveSemester();

        if (!$currentSemester) {
            return [
                'hasViolations' => false,
                'warnings' => []
            ];
        }

        // Lấy danh sách lớp đang học của sinh viên (sử dụng Repository)
        $activeEnrollments = $this->enrollmentRepository->getActiveEnrollmentsBySemester(
            $studentId, 
            $currentSemester->semester_id
        );

        // 1. Kiểm tra GPA học kỳ (sử dụng GPAWarningChecker)
        $semesterGPAWarning = $this->gpaChecker->checkSemesterGPA(
            $studentId, 
            $currentSemester->semester_id, 
            $this->minGPA
        );
        if ($semesterGPAWarning) {
            $warnings[] = $semesterGPAWarning;
        }

        // 2. Kiểm tra GPA tích lũy (sử dụng GPAWarningChecker)
        $cumulativeGPAWarning = $this->gpaChecker->checkCumulativeGPA(
            $studentId, 
            $this->graduationGPA
        );
        if ($cumulativeGPAWarning) {
            $warnings[] = $cumulativeGPAWarning;
        }

        // 3. Kiểm tra chuyên cần (sử dụng AttendanceWarningChecker)
        $attendanceWarning = $this->attendanceChecker->checkAttendance(
            $activeEnrollments, 
            $this->minAttendanceRate
        );
        if ($attendanceWarning) {
            $warnings[] = $attendanceWarning;
        }

        // 4. Kiểm tra môn rớt (sử dụng FailedCourseWarningChecker)
        $failedCoursesWarning = $this->failedCourseChecker->checkFailedCourses(
            $studentId, 
            $this->maxFailedCourses
        );
        if ($failedCoursesWarning) {
            $warnings[] = $failedCoursesWarning;
        }

        return [
            'hasViolations' => !empty($warnings),
            'warnings' => $warnings
        ];
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
        // TODO: Implement this method if needed
        return [
            'success' => false,
            'message' => 'Chức năng chưa được triển khai'
        ];
    }
}
