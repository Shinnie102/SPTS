<?php

namespace App\Services;

use App\Contracts\MajorRepositoryInterface;
use App\Contracts\FacultyRepositoryInterface;
use App\Models\Major;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class MajorService
 * 
 * Xử lý business logic liên quan đến Major
 * Tuân thủ Single Responsibility Principle (S in SOLID)
 */
class MajorService
{
    protected $majorRepository;
    protected $facultyRepository;

    public function __construct(
        MajorRepositoryInterface $majorRepository,
        FacultyRepositoryInterface $facultyRepository
    ) {
        $this->majorRepository = $majorRepository;
        $this->facultyRepository = $facultyRepository;
    }

    /**
     * Lấy danh sách majors với phân trang
     */
    public function getMajors(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->majorRepository->getPaginatedMajors($filters, $perPage);
    }

    /**
     * Lấy majors theo faculty ID
     */
    public function getMajorsByFaculty(int $facultyId)
    {
        return $this->majorRepository->getByFacultyId($facultyId);
    }

    /**
     * Lấy tất cả majors active
     */
    public function getAllActive()
    {
        return $this->majorRepository->getAllActive();
    }

    /**
     * Lấy chi tiết major
     */
    public function getMajorDetail(int $majorId): ?Major
    {
        return $this->majorRepository->findById($majorId);
    }

    /**
     * Tạo major mới
     */
    public function createMajor(array $data): array
    {
        // Validate faculty tồn tại
        if (!empty($data['faculty_id'])) {
            $faculty = $this->facultyRepository->findById($data['faculty_id']);
            if (!$faculty) {
                return [
                    'success' => false,
                    'message' => 'Khoa/Viện không tồn tại'
                ];
            }
        }

        // Kiểm tra mã chuyên ngành đã tồn tại
        $existingMajor = $this->majorRepository->findByCode($data['major_code']);

        if ($existingMajor) {
            // Mã chuyên ngành đã tồn tại -> BÁO LỖI
            return [
                'success' => false,
                'message' => 'Mã Chuyên ngành đã tồn tại'
            ];
        }

        // Kiểm tra tên chuyên ngành đã tồn tại chưa
        if ($this->majorRepository->nameExists($data['major_name'])) {
            return [
                'success' => false,
                'message' => 'Tên Chuyên ngành đã tồn tại'
            ];
        }

        // Set default status
        if (empty($data['major_status_id'])) {
            $data['major_status_id'] = 1; // Active
        }

        try {
            $major = $this->majorRepository->create($data);

            // Attach vào faculty
            if (!empty($data['faculty_id'])) {
                $this->majorRepository->attachToFaculty($major->major_id, $data['faculty_id']);
            }

            return [
                'success' => true,
                'message' => 'Thêm Chuyên ngành thành công',
                'data' => $major
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Cập nhật major
     */
    public function updateMajor(int $majorId, array $data): array
    {
        $major = $this->majorRepository->findById($majorId);

        if (!$major) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy Chuyên ngành'
            ];
        }

        // Validate mã chuyên ngành (nếu thay đổi)
        if (!empty($data['major_code']) && $data['major_code'] !== $major->major_code) {
            if ($this->majorRepository->codeExists($data['major_code'], $majorId)) {
                return [
                    'success' => false,
                    'message' => 'Mã Chuyên ngành đã tồn tại'
                ];
            }
        }

        // Validate tên chuyên ngành (nếu thay đổi)
        if (!empty($data['major_name']) && $data['major_name'] !== $major->major_name) {
            if ($this->majorRepository->nameExists($data['major_name'], $majorId)) {
                return [
                    'success' => false,
                    'message' => 'Tên Chuyên ngành đã tồn tại'
                ];
            }
        }

        try {
            $this->majorRepository->update($majorId, $data);

            return [
                'success' => true,
                'message' => 'Cập nhật Chuyên ngành thành công'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Xóa major
     */
    public function deleteMajor(int $majorId): array
    {
        $major = $this->majorRepository->findById($majorId);

        if (!$major) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy Chuyên ngành'
            ];
        }

        try {
            // Kiểm tra xem major có course không
            $courseCount = $major->courses()->count();
            
            if ($courseCount > 0) {
                return [
                    'success' => false,
                    'message' => 'Không thể xóa Chuyên ngành này vì còn ' . $courseCount . ' Học phần. Vui lòng xóa các Học phần trước.'
                ];
            }
            
            // Detach từ tất cả faculties trước khi xóa
            $major->faculties()->detach();
            
            // Xóa major
            $this->majorRepository->delete($majorId);

            return [
                'success' => true,
                'message' => 'Đã xóa Chuyên ngành thành công'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }
}