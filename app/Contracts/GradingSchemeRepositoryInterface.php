<?php

namespace App\Contracts;

use App\Models\GradingScheme;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface GradingSchemeRepositoryInterface
 * 
 * Interface cho GradingScheme Repository
 * Tuân theo Interface Segregation Principle (I in SOLID)
 * Tuân theo Dependency Inversion Principle (D in SOLID)
 */
interface GradingSchemeRepositoryInterface
{
    /**
     * Lấy tất cả grading schemes với components và số lớp đang sử dụng
     *
     * @return Collection
     */
    public function getAllWithComponents(): Collection;

    /**
     * Tìm grading scheme theo ID với components
     *
     * @param int $gradingSchemeId
     * @return GradingScheme|null
     */
    public function findById(int $gradingSchemeId): ?GradingScheme;

    /**
     * Tạo mới grading scheme
     *
     * @param array $data
     * @return GradingScheme
     */
    public function create(array $data): GradingScheme;

    /**
     * Cập nhật grading scheme
     *
     * @param int $gradingSchemeId
     * @param array $data
     * @return bool
     */
    public function update(int $gradingSchemeId, array $data): bool;

    /**
     * Xóa grading scheme
     *
     * @param int $gradingSchemeId
     * @return bool
     */
    public function delete(int $gradingSchemeId): bool;

    /**
     * Kiểm tra scheme code đã tồn tại chưa
     *
     * @param string $schemeCode
     * @param int|null $excludeId
     * @return bool
     */
    public function schemeCodeExists(string $schemeCode, ?int $excludeId = null): bool;

    /**
     * Đếm số lớp đang sử dụng grading scheme
     *
     * @param int $gradingSchemeId
     * @return int
     */
    public function countClassesUsing(int $gradingSchemeId): int;

    /**
     * Kiểm tra grading scheme có đang được sử dụng không
     *
     * @param int $gradingSchemeId
     * @return bool
     */
    public function isInUse(int $gradingSchemeId): bool;
}
