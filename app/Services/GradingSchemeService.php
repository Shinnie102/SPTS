<?php

namespace App\Services;

use App\Contracts\GradingSchemeRepositoryInterface;
use App\Models\GradingScheme;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class GradingSchemeService
 * 
 * Xử lý business logic liên quan đến GradingScheme
 * Tuân thủ Single Responsibility Principle (S in SOLID)
 */
class GradingSchemeService
{
    /**
     * @var GradingSchemeRepositoryInterface
     */
    protected $gradingSchemeRepository;

    /**
     * Constructor - Dependency Injection
     *
     * @param GradingSchemeRepositoryInterface $gradingSchemeRepository
     */
    public function __construct(GradingSchemeRepositoryInterface $gradingSchemeRepository)
    {
        $this->gradingSchemeRepository = $gradingSchemeRepository;
    }

    /**
     * Lấy tất cả grading schemes với components và số lớp đang sử dụng
     *
     * @return Collection
     */
    public function getAllGradingSchemes(): Collection
    {
        return $this->gradingSchemeRepository->getAllWithComponents();
    }

    /**
     * Lấy chi tiết grading scheme
     *
     * @param int $gradingSchemeId
     * @return GradingScheme|null
     */
    public function getGradingSchemeDetail(int $gradingSchemeId): ?GradingScheme
    {
        return $this->gradingSchemeRepository->findById($gradingSchemeId);
    }

    /**
     * Tạo grading scheme mới
     *
     * @param array $data
     * @return array ['success' => bool, 'message' => string, 'data' => GradingScheme|null]
     */
    public function createGradingScheme(array $data): array
    {
        // Validate scheme code
        if ($this->gradingSchemeRepository->schemeCodeExists($data['scheme_code'])) {
            return [
                'success' => false,
                'message' => 'Mã sơ đồ điểm đã tồn tại trong hệ thống',
                'data' => null
            ];
        }

        // Validate tổng trọng số = 100%
        if (!empty($data['components'])) {
            $totalWeight = array_sum(array_column($data['components'], 'weight_percent'));
            if ($totalWeight != 100) {
                return [
                    'success' => false,
                    'message' => 'Tổng trọng số các thành phần phải bằng 100%',
                    'data' => null
                ];
            }
        }

        try {
            $gradingScheme = $this->gradingSchemeRepository->create($data);

            return [
                'success' => true,
                'message' => 'Tạo sơ đồ điểm thành công',
                'data' => $gradingScheme
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Cập nhật grading scheme
     *
     * @param int $gradingSchemeId
     * @param array $data
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateGradingScheme(int $gradingSchemeId, array $data): array
    {
        // Kiểm tra grading scheme tồn tại
        $gradingScheme = $this->gradingSchemeRepository->findById($gradingSchemeId);
        if (!$gradingScheme) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy sơ đồ điểm'
            ];
        }

        // Validate scheme code (nếu thay đổi)
        if ($data['scheme_code'] !== $gradingScheme->scheme_code) {
            if ($this->gradingSchemeRepository->schemeCodeExists($data['scheme_code'], $gradingSchemeId)) {
                return [
                    'success' => false,
                    'message' => 'Mã sơ đồ điểm đã tồn tại trong hệ thống'
                ];
            }
        }

        // Validate tổng trọng số = 100%
        if (!empty($data['components'])) {
            $totalWeight = array_sum(array_column($data['components'], 'weight_percent'));
            if ($totalWeight != 100) {
                return [
                    'success' => false,
                    'message' => 'Tổng trọng số các thành phần phải bằng 100%'
                ];
            }
        }

        try {
            $this->gradingSchemeRepository->update($gradingSchemeId, $data);

            return [
                'success' => true,
                'message' => 'Cập nhật sơ đồ điểm thành công'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Xóa grading scheme
     *
     * @param int $gradingSchemeId
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteGradingScheme(int $gradingSchemeId): array
    {
        // Kiểm tra grading scheme tồn tại
        $gradingScheme = $this->gradingSchemeRepository->findById($gradingSchemeId);
        if (!$gradingScheme) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy sơ đồ điểm'
            ];
        }

        // Kiểm tra có đang được sử dụng không
        if ($this->gradingSchemeRepository->isInUse($gradingSchemeId)) {
            return [
                'success' => false,
                'message' => 'Không thể xóa sơ đồ điểm đang được sử dụng bởi lớp học'
            ];
        }

        try {
            $this->gradingSchemeRepository->delete($gradingSchemeId);

            return [
                'success' => true,
                'message' => 'Xóa sơ đồ điểm thành công'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Đếm số lớp đang sử dụng grading scheme
     *
     * @param int $gradingSchemeId
     * @return int
     */
    public function countClassesUsing(int $gradingSchemeId): int
    {
        return $this->gradingSchemeRepository->countClassesUsing($gradingSchemeId);
    }
}
