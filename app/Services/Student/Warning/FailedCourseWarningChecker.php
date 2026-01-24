<?php

namespace App\Services\Student\Warning;

use App\Contracts\StudentScoreRepositoryInterface;

/**
 * Class FailedCourseWarningChecker
 * 
 * Chịu trách nhiệm kiểm tra cảnh báo về môn rớt
 */
class FailedCourseWarningChecker
{
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
     * Kiểm tra môn học rớt
     * 
     * @param int $studentId
     * @param int $maxFailedCourses
     * @return array|null
     */
    public function checkFailedCourses(int $studentId, int $maxFailedCourses): ?array
    {
        $failedCourses = $this->studentScoreRepository->getFailedCourses($studentId);

        if ($failedCourses->isEmpty()) {
            return null;
        }

        $failedCount = $failedCourses->count();

        if ($failedCount < $maxFailedCourses) {
            return null;
        }

        // Vượt ngưỡng môn rớt cho phép
        $severity = $failedCount >= ($maxFailedCourses * 2) ? 'high' : 'medium';

        $courseList = $failedCourses->map(function($course) {
            return [
                'name' => $course->course_name ?? 'N/A',
                'score' => number_format($course->avg_score ?? 0, 1)
            ];
        })->toArray();

        return [
            'type' => 'failed_courses',
            'severity' => $severity,
            'title' => 'Nhiều môn học không đạt',
            'description' => "Bạn có {$failedCount} môn học không đạt, vượt ngưỡng cho phép {$maxFailedCourses} môn",
            'courses' => $courseList,
            'count' => $failedCount,
            'threshold' => $maxFailedCourses
        ];
    }
}
