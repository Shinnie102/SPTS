<?php

namespace App\Contracts;

use App\Models\Major;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface MajorRepositoryInterface
 * 
 * Interface cho Major Repository
 * Tuân thủ Interface Segregation Principle (I in SOLID)
 */
interface MajorRepositoryInterface
{
    /**
     * Lấy danh sách majors với phân trang
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginatedMajors(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    /**
     * Lấy majors theo faculty ID
     *
     * @param int $facultyId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByFacultyId(int $facultyId);

    /**
     * Lấy tất cả majors active (cho dropdown)
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllActive();

    /**
     * Tìm major theo ID
     *
     * @param int $majorId
     * @return Major|null
     */
    public function findById(int $majorId): ?Major;

    /**
     * Tìm major theo code (bao gồm cả soft deleted)
     *
     * @param string $code
     * @return Major|null
     */
    public function findByCode(string $code): ?Major;

    /**
     * Kiểm tra major code đã tồn tại chưa
     *
     * @param string $code
     * @param int|null $excludeId
     * @return bool
     */
    public function codeExists(string $code, ?int $excludeId = null): bool;

    /**
     * Kiểm tra major name đã tồn tại chưa
     *
     * @param string $name
     * @param int|null $excludeId
     * @return bool
     */
    public function nameExists(string $name, ?int $excludeId = null): bool;

    /**
     * Tạo major mới
     *
     * @param array $data
     * @return Major
     */
    public function create(array $data): Major;

    /**
     * Cập nhật major
     *
     * @param int $majorId
     * @param array $data
     * @return bool
     */
    public function update(int $majorId, array $data): bool;

    /**
     * Xóa major (soft delete)
     *
     * @param int $majorId
     * @return bool
     */
    public function delete(int $majorId): bool;

    /**
     * Attach major vào faculty
     *
     * @param int $majorId
     * @param int $facultyId
     * @return void
     */
    public function attachToFaculty(int $majorId, int $facultyId): void;

    /**
     * Xóa majors khỏi faculty
     *
     * @param int $facultyId
     * @return void
     */
    public function detachFromFaculty(int $facultyId): void;
}