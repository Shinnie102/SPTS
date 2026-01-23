<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Collection;

/**
 * Interface AttendanceRepositoryInterface
 * 
 * Contract cho Attendance Repository
 */
interface AttendanceRepositoryInterface
{
    /**
     * Tính tỷ lệ điểm danh (%)
     *
     * @param int $enrollmentId
     * @return float
     */
    public function calculateAttendanceRate(int $enrollmentId): float;

    /**
     * Đếm tổng số buổi học
     *
     * @param int $enrollmentId
     * @return int
     */
    public function countTotalClasses(int $enrollmentId): int;

    /**
     * Đếm số buổi có mặt
     *
     * @param int $enrollmentId
     * @return int
     */
    public function countPresentClasses(int $enrollmentId): int;

    /**
     * Lấy danh sách attendance của enrollment
     *
     * @param int $enrollmentId
     * @return Collection
     */
    public function getByEnrollment(int $enrollmentId): Collection;
}
