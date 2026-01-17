<?php

namespace App\Contracts;

use App\Models\Faculty;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface FacultyRepositoryInterface
 * 
 * Interface cho Faculty Repository
 * Tuân thủ Interface Segregation Principle (I in SOLID)
 */
interface FacultyRepositoryInterface
{
    /**
     * Lấy danh sách faculties với phân trang
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginatedFaculties(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    /**
     * Lấy tất cả faculties (cho dropdown)
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllActive();

    /**
     * Tìm faculty theo ID
     *
     * @param int $facultyId
     * @return Faculty|null
     */
    public function findById(int $facultyId): ?Faculty;

    /**
     * Tìm faculty theo code (bao gồm cả soft deleted)
     *
     * @param string $code
     * @return Faculty|null
     */
    public function findByCode(string $code): ?Faculty;

    /**
     * Kiểm tra faculty code đã tồn tại chưa
     *
     * @param string $code
     * @param int|null $excludeId
     * @return bool
     */
    public function codeExists(string $code, ?int $excludeId = null): bool;

    /**
     * Kiểm tra faculty name đã tồn tại chưa
     *
     * @param string $name
     * @param int|null $excludeId
     * @return bool
     */
    public function nameExists(string $name, ?int $excludeId = null): bool;

    /**
     * Tạo faculty mới
     *
     * @param array $data
     * @return Faculty
     */
    public function create(array $data): Faculty;

    /**
     * Cập nhật faculty
     *
     * @param int $facultyId
     * @param array $data
     * @return bool
     */
    public function update(int $facultyId, array $data): bool;

    /**
     * Xóa faculty (soft delete)
     *
     * @param int $facultyId
     * @return bool
     */
    public function delete(int $facultyId): bool;

    /**
     * Toggle status faculty
     *
     * @param int $facultyId
     * @param int $statusId
     * @return bool
     */
    public function toggleStatus(int $facultyId, int $statusId): bool;

    /**
     * Đếm số majors thuộc faculty
     *
     * @param int $facultyId
     * @return int
     */
    public function countMajors(int $facultyId): int;
}