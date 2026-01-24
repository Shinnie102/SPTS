<?php

namespace App\Services\Student\Warning;

use App\Contracts\StudentScoreRepositoryInterface;

/**
 * Class GPAWarningChecker
 * 
 * Chịu trách nhiệm kiểm tra cảnh báo về GPA
 * - Kiểm tra GPA học kỳ
 * - Kiểm tra GPA tích lũy
 * - Xác định mức độ nghiêm trọng
 */
class GPAWarningChecker
{
    /**
     * Critical thresholds
     */
    private const CRITICAL_GPA = 1.0;

    /**
     * @var StudentScoreRepositoryInterface
     */
    protected $studentScoreRepository;

    /**
     * Constructor
     * 
     * @param StudentScoreRepositoryInterface $studentScoreRepository
     */
    public function __construct(StudentScoreRepositoryInterface $studentScoreRepository)
    {
        $this->studentScoreRepository = $studentScoreRepository;
    }

    /**
     * Kiểm tra cảnh báo GPA học kỳ
     * 
     * @param int $studentId
     * @param int $semesterId
     * @param float $minGPA
     * @return array|null
     */
    public function checkSemesterGPA(int $studentId, int $semesterId, float $minGPA): ?array
    {
        $semesterGPA = $this->studentScoreRepository->calculateSemesterGPA($studentId, $semesterId);
        
        if ($semesterGPA === null || $semesterGPA >= $minGPA) {
            return null;
        }

        // GPA dưới ngưỡng
        $severity = $semesterGPA < self::CRITICAL_GPA ? 'high' : 'medium';
        
        return [
            'type' => 'low_gpa',
            'severity' => $severity,
            'title' => 'GPA học kỳ thấp',
            'description' => "GPA học kỳ của bạn là {$semesterGPA}, thấp hơn ngưỡng yêu cầu {$minGPA}",
            'value' => $semesterGPA,
            'threshold' => $minGPA
        ];
    }

    /**
     * Kiểm tra cảnh báo GPA tích lũy
     * 
     * @param int $studentId
     * @param float $graduationGPA
     * @return array|null
     */
    public function checkCumulativeGPA(int $studentId, float $graduationGPA): ?array
    {
        $cumulativeGPA = $this->studentScoreRepository->calculateCumulativeGPA($studentId);
        
        if ($cumulativeGPA === null || $cumulativeGPA >= $graduationGPA) {
            return null;
        }

        // GPA tích lũy dưới ngưỡng tốt nghiệp
        $severity = $cumulativeGPA < self::CRITICAL_GPA ? 'high' : 'medium';
        
        return [
            'type' => 'low_cumulative_gpa',
            'severity' => $severity,
            'title' => 'GPA tích lũy thấp',
            'description' => "GPA tích lũy của bạn là {$cumulativeGPA}, thấp hơn ngưỡng tốt nghiệp {$graduationGPA}",
            'value' => $cumulativeGPA,
            'threshold' => $graduationGPA
        ];
    }
}
