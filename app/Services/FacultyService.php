<?php

namespace App\Services;

use App\Contracts\FacultyRepositoryInterface;
use App\Contracts\MajorRepositoryInterface;
use App\Models\Faculty;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class FacultyService
 * 
 * Xử lý business logic liên quan đến Faculty
 * Tuân thủ Single Responsibility Principle (S in SOLID)
 */
class FacultyService
{
    protected $facultyRepository;
    protected $majorRepository;

    public function __construct(
        FacultyRepositoryInterface $facultyRepository,
        MajorRepositoryInterface $majorRepository
    ) {
        $this->facultyRepository = $facultyRepository;
        $this->majorRepository = $majorRepository;
    }

    /**
     * Lấy danh sách faculties với phân trang
     */
    public function getFaculties(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->facultyRepository->getPaginatedFaculties($filters, $perPage);
    }

    /**
     * Lấy tất cả faculties active (cho dropdown)
     */
    public function getAllActive()
    {
        return $this->facultyRepository->getAllActive();
    }

    /**
     * Lấy chi tiết faculty với majors
     */
    public function getFacultyDetail(int $facultyId): ?Faculty
    {
        return $this->facultyRepository->findById($facultyId);
    }

    /**
     * Tạo faculty mới
     */
    public function createFaculty(array $data): array
    {
        // Kiểm tra mã khoa đã tồn tại (bao gồm soft deleted)
        $existingFaculty = $this->facultyRepository->findByCode($data['faculty_code']);

        if ($existingFaculty) {
            if ($existingFaculty->trashed()) {
                // Nếu đã bị soft delete -> RESTORE
                $existingFaculty->restore();
                $existingFaculty->update([
                    'faculty_name' => $data['faculty_name'],
                    'faculty_status_id' => $data['faculty_status_id'] ?? 1,
                ]);

                return [
                    'success' => true,
                    'message' => 'Đã khôi phục và cập nhật Khoa/Viện thành công',
                    'data' => $existingFaculty
                ];
            } else {
                // Nếu đang active -> BÁO LỖI
                return [
                    'success' => false,
                    'message' => 'Mã Khoa/Viện đã tồn tại'
                ];
            }
        }

        // Kiểm tra tên khoa đã tồn tại chưa
        if ($this->facultyRepository->nameExists($data['faculty_name'])) {
            return [
                'success' => false,
                'message' => 'Tên Khoa/Viện đã tồn tại'
            ];
        }

        // Set default status
        if (empty($data['faculty_status_id'])) {
            $data['faculty_status_id'] = 1; // Active
        }

        try {
            $faculty = $this->facultyRepository->create($data);

            return [
                'success' => true,
                'message' => 'Thêm Khoa/Viện thành công',
                'data' => $faculty
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Cập nhật faculty
     */
    public function updateFaculty(int $facultyId, array $data): array
    {
        $faculty = $this->facultyRepository->findById($facultyId);

        if (!$faculty) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy Khoa/Viện'
            ];
        }

        // Validate mã khoa (nếu thay đổi)
        if (!empty($data['faculty_code']) && $data['faculty_code'] !== $faculty->faculty_code) {
            if ($this->facultyRepository->codeExists($data['faculty_code'], $facultyId)) {
                return [
                    'success' => false,
                    'message' => 'Mã Khoa/Viện đã tồn tại'
                ];
            }
        }

        // Validate tên khoa (nếu thay đổi)
        if (!empty($data['faculty_name']) && $data['faculty_name'] !== $faculty->faculty_name) {
            if ($this->facultyRepository->nameExists($data['faculty_name'], $facultyId)) {
                return [
                    'success' => false,
                    'message' => 'Tên Khoa/Viện đã tồn tại'
                ];
            }
        }

        try {
            $this->facultyRepository->update($facultyId, $data);

            return [
                'success' => true,
                'message' => 'Cập nhật Khoa/Viện thành công'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Xóa faculty (cascade delete majors)
     */
    public function deleteFaculty(int $facultyId): array
    {
        $faculty = $this->facultyRepository->findById($facultyId);

        if (!$faculty) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy Khoa/Viện'
            ];
        }

        try {
            // Kiểm tra số lượng chuyên ngành thuộc khoa này
            $majors = $this->majorRepository->getByFacultyId($facultyId);
            
            if ($majors->count() > 0) {
                return [
                    'success' => false,
                    'message' => 'Không thể xóa Khoa/Viện này vì còn ' . $majors->count() . ' Chuyên ngành. Vui lòng xóa các Chuyên ngành trước.'
                ];
            }

            // Xóa relations trong bảng faculty_major (nếu còn)
            $faculty->majors()->detach();

            // Hard delete faculty
            $this->facultyRepository->delete($facultyId);

            return [
                'success' => true,
                'message' => 'Đã xóa Khoa/Viện thành công'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Toggle status faculty
     */
    public function toggleStatus(int $facultyId): array
    {
        $faculty = $this->facultyRepository->findById($facultyId);

        if (!$faculty) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy Khoa/Viện'
            ];
        }

        // Toggle status: 1 (Active) <-> 2 (Inactive)
        $newStatusId = $faculty->faculty_status_id == 1 ? 2 : 1;

        try {
            $this->facultyRepository->toggleStatus($facultyId, $newStatusId);

            $statusText = $newStatusId == 1 ? 'kích hoạt' : 'ngừng hoạt động';

            return [
                'success' => true,
                'message' => "Đã {$statusText} Khoa/Viện thành công",
                'new_status' => $newStatusId
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }
}