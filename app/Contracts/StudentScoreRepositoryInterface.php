<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Collection;

/**
 * Interface StudentScoreRepositoryInterface
 * 
 * Contract cho StudentScore Repository
 */
interface StudentScoreRepositoryInterface
{
    /**
     * Tính điểm trung bình các thành phần
     *
     * @param int $enrollmentId
     * @return float|null
     */
    public function calculateAverageScore(int $enrollmentId): ?float;

    /**
     * Tính GPA học kỳ
     *
     * @param int $studentId
     * @param int $semesterId
     * @return float|null
     */
    public function calculateSemesterGPA(int $studentId, int $semesterId): ?float;

    /**
     * Tính GPA tổng tích lũy
     *
     * @param int $studentId
     * @return float|null
     */
    public function calculateCumulativeGPA(int $studentId): ?float;

    /**
     * Lấy danh sách điểm của enrollment
     *
     * @param int $enrollmentId
     * @return Collection
     */
    public function getByEnrollment(int $enrollmentId): Collection;

    /**
     * Lấy danh sách môn rớt
     *
     * @param int $studentId
     * @return Collection
     */
    public function getFailedCourses(int $studentId): Collection;
}
