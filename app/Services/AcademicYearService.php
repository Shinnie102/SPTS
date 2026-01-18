<?php

namespace App\Services;

use App\Contracts\AcademicYearRepositoryInterface;
use App\Contracts\SemesterRepositoryInterface;
use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class AcademicYearService
 * 
 * Xử lý business logic liên quan đến AcademicYear
 * Tuân theo Single Responsibility Principle (S in SOLID)
 */
class AcademicYearService
{
    protected $academicYearRepository;
    protected $semesterRepository;

    public function __construct(
        AcademicYearRepositoryInterface $academicYearRepository,
        SemesterRepositoryInterface $semesterRepository
    ) {
        $this->academicYearRepository = $academicYearRepository;
        $this->semesterRepository = $semesterRepository;
    }

    /**
     * Lấy tất cả năm học với thông tin học kỳ và số lượng lớp
     * 
     * @return array
     */
    public function getAllAcademicYearsWithDetails(): array
    {
        // Cập nhật trạng thái tự động trước khi lấy dữ liệu
        $this->academicYearRepository->updateStatusByDate();
        $this->semesterRepository->updateStatusByDate();

        $academicYears = $this->academicYearRepository->getAllWithSemesters();

        $result = [];

        foreach ($academicYears as $year) {
            $semesters = [];
            
            foreach ($year->semesters as $semester) {
                $semesters[] = [
                    'semester_id' => $semester->semester_id,
                    'semester_code' => $semester->semester_code,
                    'start_date' => $semester->start_date->format('d/m/Y'),
                    'end_date' => $semester->end_date->format('d/m/Y'),
                    'status_code' => $semester->status->code,
                    'status_name' => $semester->status->name,
                    'class_count' => $this->semesterRepository->countClassSections($semester->semester_id),
                ];
            }

            $result[] = [
                'academic_year_id' => $year->academic_year_id,
                'year_code' => $year->year_code,
                'start_date' => $year->start_date->format('d/m/Y'),
                'end_date' => $year->end_date->format('d/m/Y'),
                'status_code' => $year->status->code,
                'status_name' => $year->status->name,
                'semester_count' => count($semesters),
                'semesters' => $semesters,
            ];
        }

        return $result;
    }

    /**
     * Lấy chi tiết năm học
     * 
     * @param int $academicYearId
     * @return AcademicYear|null
     */
    public function getAcademicYearDetail(int $academicYearId): ?AcademicYear
    {
        return $this->academicYearRepository->findById($academicYearId);
    }

    /**
     * Tạo năm học mới
     * 
     * @param array $data
     * @return array
     */
    public function createAcademicYear(array $data): array
    {
        // Validate year_code
        if ($this->academicYearRepository->yearCodeExists($data['year_code'])) {
            return [
                'success' => false,
                'message' => 'Mã năm học đã tồn tại'
            ];
        }

        // Validate dates
        if ($data['start_date'] >= $data['end_date']) {
            return [
                'success' => false,
                'message' => 'Ngày bắt đầu phải trước ngày kết thúc'
            ];
        }

        // Xác định status_id dựa trên thời gian
        $now = now()->toDateString();
        if ($data['start_date'] <= $now && $data['end_date'] >= $now) {
            $data['status_id'] = 1; // ACTIVE
        } else {
            $data['status_id'] = 2; // COMPLETED hoặc chưa bắt đầu
        }

        try {
            $academicYear = $this->academicYearRepository->create($data);

            return [
                'success' => true,
                'message' => 'Thêm năm học thành công',
                'data' => $academicYear
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Xóa năm học
     * 
     * @param int $academicYearId
     * @return array
     */
    public function deleteAcademicYear(int $academicYearId): array
    {
        $academicYear = $this->academicYearRepository->findById($academicYearId);

        if (!$academicYear) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy năm học'
            ];
        }

        // Kiểm tra có học kỳ không
        $semesterCount = $this->academicYearRepository->countSemesters($academicYearId);

        if ($semesterCount > 0) {
            return [
                'success' => false,
                'message' => 'Không thể xóa năm học vì còn ' . $semesterCount . ' học kỳ. Vui lòng xóa các học kỳ trước.'
            ];
        }

        try {
            $this->academicYearRepository->delete($academicYearId);

            return [
                'success' => true,
                'message' => 'Đã xóa năm học thành công'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }
}