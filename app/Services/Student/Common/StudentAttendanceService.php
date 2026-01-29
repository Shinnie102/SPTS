<?php

namespace App\Services\Student\Common;

use App\Contracts\EnrollmentRepositoryInterface;
use App\Services\Student\Score\ScoreSemesterGrouper;

/**
 * Class StudentAttendanceService
 */
class StudentAttendanceService
{
    /**
     * @var EnrollmentRepositoryInterface
     */
    protected $enrollmentRepository;

    /**
     * @var AttendanceStatisticsCalculator
     */
    protected $statisticsCalculator;

    /**
     * @var AttendanceStatusDeterminer
     */
    protected $statusDeterminer;

    /**
     * @var ScoreSemesterGrouper
     */
    protected $semesterGrouper;

    /**
     * Constructor - Dependency Injection
     * 
     * @param EnrollmentRepositoryInterface $enrollmentRepository
     * @param AttendanceStatisticsCalculator $statisticsCalculator
     * @param AttendanceStatusDeterminer $statusDeterminer
     * @param ScoreSemesterGrouper $semesterGrouper
     */
    public function __construct(
        EnrollmentRepositoryInterface $enrollmentRepository,
        AttendanceStatisticsCalculator $statisticsCalculator,
        AttendanceStatusDeterminer $statusDeterminer,
        ScoreSemesterGrouper $semesterGrouper
    ) {
        $this->enrollmentRepository = $enrollmentRepository;
        $this->statisticsCalculator = $statisticsCalculator;
        $this->statusDeterminer = $statusDeterminer;
        $this->semesterGrouper = $semesterGrouper;
    }

    /**
     * Get attendance summary for a student
     * 
     * Orchestrates: enrollment retrieval → stats calculation → status determination → grouping
     * 
     * @param int $studentId
     * @return array
     */
    public function getStudentAttendanceSummary(int $studentId): array
    {
        // Lấy enrollments từ Repository
        $enrollments = $this->enrollmentRepository->getAllByStudent($studentId);

        // Tính stats cho từng enrollment
        $courseStats = [];
        foreach ($enrollments as $enrollment) {
            $stats = $this->statisticsCalculator->calculateEnrollmentStats($enrollment);
            
            if ($stats) {
                // Thêm status vào stats
                $stats['status'] = $this->statusDeterminer->determineStatus($stats['percentage']);
                $courseStats[] = $stats;
            }
        }

        // Group by semester với progress
        $groupedSemesters = $this->groupBySemesterWithProgress($courseStats);
        
        // Tính tổng progress
        $totalProgress = $this->statisticsCalculator->calculateTotalProgress($courseStats);

        return [
            'totalProgress' => $totalProgress,
            'semesters' => $groupedSemesters
        ];
    }

    /**
     * Group course stats by semester with progress calculation
     * 
     * Delegates semester formatting to ScoreSemesterGrouper
     * 
     * @param array $courseStats
     * @return array
     */
    protected function groupBySemesterWithProgress(array $courseStats): array
    {
        $grouped = [];
        
        foreach ($courseStats as $stat) {
            // Tạo key duy nhất cho semester bằng cách kết hợp semester code và year
            $semesterKey = $stat['semesterName'] . '_' . $stat['year'];
            
            if (!isset($grouped[$semesterKey])) {
                // Tạo semester name format "Học kỳ X - Năm YYYY-YYYY"
                $semesterCode = $stat['semesterName'];
                $yearCode = $stat['year'];
                
                if (preg_match('/HK(\d+)/', $semesterCode, $matches)) {
                    $semesterNumber = $matches[1];
                    $semesterName = "Học kỳ {$semesterNumber} - Năm {$yearCode}";
                } else {
                    $semesterName = $semesterCode;
                }
                
                // Sử dụng ScoreSemesterGrouper để tạo sort key
                $sortKey = $this->semesterGrouper->createSortKey($semesterKey);
                
                $grouped[$semesterKey] = [
                    'semester_name' => $semesterName,
                    'sort_key' => $sortKey,
                    'progress' => 0,
                    'courses' => []
                ];
            }
            
            $grouped[$semesterKey]['courses'][] = $stat;
        }
        
        // Tính progress cho từng semester sử dụng AttendanceStatisticsCalculator
        foreach ($grouped as $semesterKey => &$semesterData) {
            $semesterData['progress'] = $this->statisticsCalculator->calculateSemesterProgress($semesterData['courses']);
        }
        
        return $grouped;
    }
}
