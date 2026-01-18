<?php

namespace App\Contracts;

use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface AcademicYearRepositoryInterface
 * 
 * Định nghĩa các phương thức cho AcademicYear Repository
 * Tuân theo Dependency Inversion Principle (D in SOLID)
 */
interface AcademicYearRepositoryInterface
{
    /**
     * Lấy tất cả năm học, sắp xếp theo trạng thái và thời gian
     * 
     * @return Collection
     */
    public function getAllWithSemesters(): Collection;

    /**
     * Tìm năm học theo ID
     * 
     * @param int $academicYearId
     * @return AcademicYear|null
     */
    public function findById(int $academicYearId): ?AcademicYear;

    /**
     * Tạo năm học mới
     * 
     * @param array $data
     * @return AcademicYear
     */
    public function create(array $data): AcademicYear;

    /**
     * Cập nhật năm học
     * 
     * @param int $academicYearId
     * @param array $data
     * @return bool
     */
    public function update(int $academicYearId, array $data): bool;

    /**
     * Xóa năm học
     * 
     * @param int $academicYearId
     * @return bool
     */
    public function delete(int $academicYearId): bool;

    /**
     * Kiểm tra mã năm học đã tồn tại chưa
     * 
     * @param string $yearCode
     * @param int|null $excludeId
     * @return bool
     */
    public function yearCodeExists(string $yearCode, ?int $excludeId = null): bool;

    /**
     * Đếm số học kỳ của năm học
     * 
     * @param int $academicYearId
     * @return int
     */
    public function countSemesters(int $academicYearId): int;

    /**
     * Cập nhật trạng thái năm học tự động dựa trên thời gian
     * 
     * @return void
     */
    public function updateStatusByDate(): void;
}