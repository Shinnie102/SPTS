<?php

namespace App\Services;

use App\Contracts\SemesterRepositoryInterface;
use App\Contracts\AcademicYearRepositoryInterface;
use App\Models\Semester;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class SemesterService
 * 
 * Xử lý business logic liên quan đến Semester
 * Tuân theo Single Responsibility Principle (S in SOLID)
 */
class SemesterService
{
    protected $semesterRepository;
    protected $academicYearRepository;

    public function __construct(
        SemesterRepositoryInterface $semesterRepository,
        AcademicYearRepositoryInterface $academicYearRepository
    ) {
        $this->semesterRepository = $semesterRepository;
        $this->academicYearRepository = $academicYearRepository;
    }

    /**
     * Lấy tất cả học kỳ theo năm học
     * 
     * @param int $academicYearId
     * @return Collection
     */
    public function getSemestersByAcademicYear(int $academicYearId): Collection
    {
        return $this->semesterRepository->getByAcademicYear($academicYearId);
    }

    /**
     * Lấy chi tiết học kỳ
     * 
     * @param int $semesterId
     * @return Semester|null
     */
    public function getSemesterDetail(int $semesterId): ?Semester
    {
        return $this->semesterRepository->findById($semesterId);
    }

    /**
     * Tạo học kỳ mới
     * 
     * @param array $data
     * @return array
     */
    public function createSemester(array $data): array
    {
        // Validate academic_year tồn tại
        $academicYear = $this->academicYearRepository->findById($data['academic_year_id']);

        if (!$academicYear) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy năm học'
            ];
        }

        // Validate semester_code trong năm học
        if ($this->semesterRepository->semesterCodeExists($data['academic_year_id'], $data['semester_code'])) {
            return [
                'success' => false,
                'message' => 'Mã học kỳ đã tồn tại trong năm học này'
            ];
        }

        // Validate dates
        if ($data['start_date'] >= $data['end_date']) {
            return [
                'success' => false,
                'message' => 'Ngày bắt đầu phải trước ngày kết thúc'
            ];
        }

        // Validate học kỳ phải nằm trong năm học
        if ($data['start_date'] < $academicYear->start_date->toDateString() || 
            $data['end_date'] > $academicYear->end_date->toDateString()) {
            return [
                'success' => false,
                'message' => 'Học kỳ phải nằm trong khoảng thời gian của năm học'
            ];
        }

        // Xác định status_id dựa trên thời gian
        $now = now()->toDateString();
        if ($data['start_date'] <= $now && $data['end_date'] >= $now) {
            $data['status_id'] = 1; // ONGOING
        } elseif ($data['end_date'] < $now) {
            $data['status_id'] = 2; // COMPLETED
        } else {
            $data['status_id'] = 3; // UPCOMING
        }

        try {
            $semester = $this->semesterRepository->create($data);

            return [
                'success' => true,
                'message' => 'Thêm học kỳ thành công',
                'data' => $semester
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Cập nhật học kỳ
     * 
     * @param int $semesterId
     * @param array $data
     * @return array
     */
    public function updateSemester(int $semesterId, array $data): array
    {
        $semester = $this->semesterRepository->findById($semesterId);

        if (!$semester) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy học kỳ'
            ];
        }

        // Validate semester_code (nếu thay đổi)
        if (!empty($data['semester_code']) && $data['semester_code'] !== $semester->semester_code) {
            if ($this->semesterRepository->semesterCodeExists(
                $semester->academic_year_id, 
                $data['semester_code'], 
                $semesterId
            )) {
                return [
                    'success' => false,
                    'message' => 'Mã học kỳ đã tồn tại trong năm học này'
                ];
            }
        }

        // Validate dates
        $startDate = $data['start_date'] ?? $semester->start_date->toDateString();
        $endDate = $data['end_date'] ?? $semester->end_date->toDateString();

        if ($startDate >= $endDate) {
            return [
                'success' => false,
                'message' => 'Ngày bắt đầu phải trước ngày kết thúc'
            ];
        }

        // Validate với năm học
        $academicYear = $semester->academicYear;
        if ($startDate < $academicYear->start_date->toDateString() || 
            $endDate > $academicYear->end_date->toDateString()) {
            return [
                'success' => false,
                'message' => 'Học kỳ phải nằm trong khoảng thời gian của năm học'
            ];
        }

        // Cập nhật status_id dựa trên thời gian
        $now = now()->toDateString();
        if ($startDate <= $now && $endDate >= $now) {
            $data['status_id'] = 1; // ONGOING
        } elseif ($endDate < $now) {
            $data['status_id'] = 2; // COMPLETED
        } else {
            $data['status_id'] = 3; // UPCOMING
        }

        try {
            $this->semesterRepository->update($semesterId, $data);

            return [
                'success' => true,
                'message' => 'Cập nhật học kỳ thành công'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Xóa học kỳ
     * 
     * @param int $semesterId
     * @return array
     */
    public function deleteSemester(int $semesterId): array
    {
        $semester = $this->semesterRepository->findById($semesterId);

        if (!$semester) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy học kỳ'
            ];
        }

        // Kiểm tra có lớp học phần không
        $classCount = $this->semesterRepository->countClassSections($semesterId);

        if ($classCount > 0) {
            return [
                'success' => false,
                'message' => 'Không thể xóa học kỳ vì còn ' . $classCount . ' lớp học phần. Vui lòng xóa các lớp học phần trước.'
            ];
        }

        try {
            $this->semesterRepository->delete($semesterId);

            return [
                'success' => true,
                'message' => 'Đã xóa học kỳ thành công'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }
}