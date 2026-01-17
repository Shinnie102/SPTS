<?php

namespace App\Contracts;

use App\Models\Course;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface CourseRepositoryInterface
 * 
 * Interface cho Course Repository
 * Tuân thủ Interface Segregation Principle (I in SOLID)
 */
interface CourseRepositoryInterface
{
    /**
     * Lấy danh sách courses với phân trang
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginatedCourses(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    /**
     * Tìm course theo ID
     *
     * @param int $courseId
     * @return Course|null
     */
    public function findById(int $courseId): ?Course;

    /**
     * Tìm course theo code (bao gồm cả soft deleted)
     *
     * @param string $code
     * @return Course|null
     */
    public function findByCode(string $code): ?Course;

    /**
     * Kiểm tra course code đã tồn tại chưa
     *
     * @param string $code
     * @param int|null $excludeId
     * @return bool
     */
    public function codeExists(string $code, ?int $excludeId = null): bool;

    /**
     * Tạo course mới
     *
     * @param array $data
     * @return Course
     */
    public function create(array $data): Course;

    /**
     * Cập nhật course
     *
     * @param int $courseId
     * @param array $data
     * @return bool
     */
    public function update(int $courseId, array $data): bool;

    /**
     * Xóa course (soft delete)
     *
     * @param int $courseId
     * @return bool
     */
    public function delete(int $courseId): bool;

    /**
     * Toggle lock/unlock course (chỉ ADMIN thấy khi locked)
     *
     * @param int $courseId
     * @param int $statusId
     * @return bool
     */
    public function toggleLock(int $courseId, int $statusId): bool;

    /**
     * Sync course với majors
     *
     * @param int $courseId
     * @param array $majorIds
     * @return void
     */
    public function syncMajors(int $courseId, array $majorIds): void;
}