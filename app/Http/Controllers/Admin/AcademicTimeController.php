<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AcademicYearService;
use App\Services\SemesterService;
use App\Http\Requests\StoreAcademicYearRequest;
use App\Http\Requests\StoreSemesterRequest;
use App\Http\Requests\UpdateSemesterRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Class AcademicTimeController
 * 
 * Controller xử lý Thời gian Học vụ (Năm học và Học kỳ)
 * Tuân theo Single Responsibility Principle (S in SOLID)
 */
class AcademicTimeController extends Controller
{
    protected $academicYearService;
    protected $semesterService;

    public function __construct(
        AcademicYearService $academicYearService,
        SemesterService $semesterService
    ) {
        $this->academicYearService = $academicYearService;
        $this->semesterService = $semesterService;
    }

    /**
     * Hiển thị trang Thời gian Học vụ
     */
    public function index(): View
    {
        return view('admin.adminThoigian');
    }

    // ==================== ACADEMIC YEAR APIs ====================

    /**
     * API: Lấy tất cả năm học với thông tin học kỳ
     */
    public function getAcademicYears(): JsonResponse
    {
        try {
            $data = $this->academicYearService->getAllAcademicYearsWithDetails();

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Tạo năm học mới
     */
    public function storeAcademicYear(StoreAcademicYearRequest $request): JsonResponse
    {
        $result = $this->academicYearService->createAcademicYear($request->validated());

        $statusCode = $result['success'] ? 201 : 422;

        return response()->json($result, $statusCode);
    }

    /**
     * API: Xóa năm học
     */
    public function deleteAcademicYear(int $academicYearId): JsonResponse
    {
        $result = $this->academicYearService->deleteAcademicYear($academicYearId);

        $statusCode = $result['success'] ? 200 : 422;

        return response()->json($result, $statusCode);
    }

    // ==================== SEMESTER APIs ====================

    /**
     * API: Lấy chi tiết học kỳ
     */
    public function showSemester(int $semesterId): JsonResponse
    {
        $semester = $this->semesterService->getSemesterDetail($semesterId);

        if (!$semester) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy học kỳ'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'semester_id' => $semester->semester_id,
                'semester_code' => $semester->semester_code,
                'start_date' => $semester->start_date->format('Y-m-d'),
                'end_date' => $semester->end_date->format('Y-m-d'),
                'academic_year_id' => $semester->academic_year_id,
                'academic_year_code' => $semester->academicYear->year_code,
            ]
        ]);
    }

    /**
     * API: Tạo học kỳ mới
     */
    public function storeSemester(StoreSemesterRequest $request): JsonResponse
    {
        $result = $this->semesterService->createSemester($request->validated());

        $statusCode = $result['success'] ? 201 : 422;

        return response()->json($result, $statusCode);
    }

    /**
     * API: Cập nhật học kỳ
     */
    public function updateSemester(UpdateSemesterRequest $request, int $semesterId): JsonResponse
    {
        $result = $this->semesterService->updateSemester($semesterId, $request->validated());

        $statusCode = $result['success'] ? 200 : 422;

        return response()->json($result, $statusCode);
    }

    /**
     * API: Xóa học kỳ
     */
    public function deleteSemester(int $semesterId): JsonResponse
    {
        $result = $this->semesterService->deleteSemester($semesterId);

        $statusCode = $result['success'] ? 200 : 422;

        return response()->json($result, $statusCode);
    }
}