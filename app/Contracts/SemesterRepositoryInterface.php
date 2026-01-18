<?php

namespace App\Contracts;

use App\Models\Semester;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface SemesterRepositoryInterface
 * 
 * Định nghĩa các phương thức cho Semester Repository
 * Tuân theo Dependency Inversion Principle (D in SOLID)
 */
interface SemesterRepositoryInterface
{
    /**
     * Lấy tất cả học kỳ theo năm học
     * 
     * @param int $academicYearId
     * @return Collection
     */
    public function getByAcademicYear(int $academicYearId): Collection;

    /**
     * Tìm học kỳ theo ID
     * 
     * @param int $semesterId
     * @return Semester|null
     */
    public function findById(int $semesterId): ?Semester;

    /**
     * Tạo học kỳ mới
     * 
     * @param array $data
     * @return Semester
     */
    public function create(array $data): Semester;

    /**
     * Cập nhật học kỳ
     * 
     * @param int $semesterId
     * @param array $data
     * @return bool
     */
    public function update(int $semesterId, array $data): bool;

    /**
     * Xóa học kỳ
     * 
     * @param int $semesterId
     * @return bool
     */
    public function delete(int $semesterId): bool;

    /**
     * Kiểm tra mã học kỳ đã tồn tại trong năm học chưa
     * 
     * @param int $academicYearId
     * @param string $semesterCode
     * @param int|null $excludeId
     * @return bool
     */
    public function semesterCodeExists(int $academicYearId, string $semesterCode, ?int $excludeId = null): bool;

    /**
     * Đếm số lớp học phần của học kỳ
     * 
     * @param int $semesterId
     * @return int
     */
    public function countClassSections(int $semesterId): int;

    /**
     * Cập nhật trạng thái học kỳ tự động dựa trên thời gian
     * 
     * @return void
     */
    public function updateStatusByDate(): void;
}