<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ClassSectionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Class ClassSectionController
 * 
 * Xử lý requests liên quan đến ClassSection (Lớp học)
 * Tuân thủ:
 * - Single Responsibility: Chỉ xử lý HTTP requests
 * - Dependency Injection: Inject Service thay vì tạo mới
 * - REST Convention: Tách biệt giữa HTML views và API responses
 */
class ClassSectionController extends Controller
{
    protected $classService;

    /**
     * Constructor - Dependency Injection
     * 
     * @param ClassSectionService $classService
     */
    public function __construct(ClassSectionService $classService)
    {
        $this->classService = $classService;
    }

    /**
     * Hiển thị trang danh sách Lớp học
     * 
     * @return View
     */
    public function index(): View
    {
        return view('admin.adminLophoc');
    }

    /**
     * API: Lấy danh sách lớp học với filter
     * 
     * Các filter parameter:
     * - keyword: Tìm kiếm theo mã lớp hoặc tên môn học
     * - faculty_id: Lọc theo Khoa
     * - major_id: Lọc theo Chuyên ngành
     * - semester_id: Lọc theo Học kỳ
     * - status_id: Lọc theo Trạng thái
     * - page: Trang hiện tại
     * - per_page: Số bản ghi mỗi trang
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getClassSections(Request $request): JsonResponse
    {
        // Lấy filters từ request
        $filters = [
            'keyword' => $request->get('keyword'),
            'faculty_id' => $request->get('faculty_id'),
            'major_id' => $request->get('major_id'),
            'semester_id' => $request->get('semester_id'),
            'status_id' => $request->get('status_id'),
        ];

        // Lấy per_page, mặc định 15
        $perPage = $request->get('per_page', 15);

        try {
            // Gọi service để lấy dữ liệu
            $result = $this->classService->getFilteredClassSections($filters, $perPage);

            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'pagination' => $result['pagination'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy dữ liệu: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Lấy danh sách các option cho filters
     * (Faculties, Majors, Semesters, Statuses)
     * 
     * @return JsonResponse
     */
    public function getFilterOptions(): JsonResponse
    {
        try {
            $options = $this->classService->getFilterOptions();

            return response()->json([
                'success' => true,
                'data' => $options,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy options: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Lấy danh sách chuyên ngành theo khoa
     */
    public function getMajorsByFaculty(Request $request): JsonResponse
    {
        try {
            $facultyId = (int) $request->get('faculty_id', 0);
            $options = $this->classService->getFilterOptions();
            // Gọi lại repository trực tiếp qua service để lấy majors theo faculty
            $majors = app(\App\Repositories\ClassSectionRepository::class)->getMajorsByFaculty($facultyId);

            return response()->json([
                'success' => true,
                'data' => [ 'majors' => $majors ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy chuyên ngành: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Lấy chi tiết một lớp học
     * 
     * @param int $id ID của lớp học
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $classSection = $this->classService->getClassSectionDetail($id);

            if (!$classSection) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy lớp học',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $classSection,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy chi tiết: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Xóa cứng một lớp học phần và toàn bộ dữ liệu liên quan
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->classService->deleteClassSection($id);
            return response()->json([
                'success' => true,
                'message' => 'Xóa lớp học phần thành công',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa lớp học phần: ' . $e->getMessage(),
            ], 500);
        }
    }
}
