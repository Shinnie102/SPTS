<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Collection;

/**
 * Interface EnrollmentRepositoryInterface
 * 
 * Contract cho Enrollment Repository
 */
interface EnrollmentRepositoryInterface
{
    /**
     * Lấy enrollments đang active của sinh viên trong học kỳ
     *
     * @param int $studentId
     * @param int $semesterId
     * @return Collection
     */
    public function getActiveEnrollmentsBySemester(int $studentId, int $semesterId): Collection;

    /**
     * Lấy enrollment theo student và class section
     *
     * @param int $studentId
     * @param int $classSectionId
     * @return \App\Models\Enrollment|null
     */
    public function findByStudentAndClassSection(int $studentId, int $classSectionId);

    /**
     * Lấy tất cả enrollments của sinh viên
     *
     * @param int $studentId
     * @return Collection
     */
    public function getAllByStudent(int $studentId): Collection;

    /**
     * Đếm enrollments theo status
     *
     * @param int $studentId
     * @param int $statusId
     * @return int
     */
    public function countByStudentAndStatus(int $studentId, int $statusId): int;
}
