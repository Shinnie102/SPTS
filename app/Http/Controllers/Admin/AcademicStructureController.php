<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FacultyService;
use App\Services\MajorService;
use App\Services\CourseService;
use App\Http\Requests\StoreFacultyRequest;
use App\Http\Requests\UpdateFacultyRequest;
use App\Http\Requests\StoreMajorRequest;
use App\Http\Requests\UpdateMajorRequest;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Class AcademicStructureController
 * 
 * Controller xử lý Cấu trúc Học thuật (Khoa/Viện, Chuyên ngành, Học phần)
 */
class AcademicStructureController extends Controller
{
    protected $facultyService;
    protected $majorService;
    protected $courseService;

    public function __construct(
        FacultyService $facultyService,
        MajorService $majorService,
        CourseService $courseService
    ) {
        $this->facultyService = $facultyService;
        $this->majorService = $majorService;
        $this->courseService = $courseService;
    }

    /**
     * Hiển thị trang Cấu trúc Học thuật
     */
    public function index(): View
    {
        return view('admin.adminhocthuat');
    }

    // ==================== FACULTY APIs ====================

    /**
     * API: Lấy danh sách faculties
     */
    public function getFaculties(Request $request): JsonResponse
    {
        $filters = [
            'keyword' => $request->get('keyword'),
            'status' => $request->get('status'),
        ];

        $perPage = $request->get('per_page', 50); // Load nhiều để hiển thị dạng card

        $faculties = $this->facultyService->getFaculties($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'faculties' => $faculties->items(),
                'pagination' => [
                    'current_page' => $faculties->currentPage(),
                    'last_page' => $faculties->lastPage(),
                    'total' => $faculties->total(),
                ]
            ]
        ]);
    }

    /**
     * API: Tạo faculty mới
     */
    public function storeFaculty(StoreFacultyRequest $request): JsonResponse
    {
        $result = $this->facultyService->createFaculty($request->validated());

        $statusCode = $result['success'] ? 201 : 422;

        return response()->json($result, $statusCode);
    }

    /**
     * API: Lấy chi tiết faculty
     */
    public function showFaculty(int $facultyId): JsonResponse
    {
        $faculty = $this->facultyService->getFacultyDetail($facultyId);

        if (!$faculty) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy Khoa/Viện'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $faculty
        ]);
    }

    /**
     * API: Cập nhật faculty
     */
    public function updateFaculty(UpdateFacultyRequest $request, int $facultyId): JsonResponse
    {
        $result = $this->facultyService->updateFaculty($facultyId, $request->validated());

        $statusCode = $result['success'] ? 200 : 422;

        return response()->json($result, $statusCode);
    }

    /**
     * API: Xóa faculty
     */
    public function deleteFaculty(int $facultyId): JsonResponse
    {
        $result = $this->facultyService->deleteFaculty($facultyId);

        $statusCode = $result['success'] ? 200 : 422;

        return response()->json($result, $statusCode);
    }

    /**
     * API: Toggle status faculty
     */
    public function toggleFacultyStatus(int $facultyId): JsonResponse
    {
        $result = $this->facultyService->toggleStatus($facultyId);

        $statusCode = $result['success'] ? 200 : 422;

        return response()->json($result, $statusCode);
    }

    // ==================== MAJOR APIs ====================

    /**
     * API: Lấy majors theo faculty
     */
    public function getMajorsByFaculty(int $facultyId): JsonResponse
    {
        $majors = $this->majorService->getMajorsByFaculty($facultyId);

        return response()->json([
            'success' => true,
            'data' => $majors
        ]);
    }

    /**
     * API: Tạo major mới
     */
    public function storeMajor(StoreMajorRequest $request): JsonResponse
    {
        $result = $this->majorService->createMajor($request->validated());

        $statusCode = $result['success'] ? 201 : 422;

        return response()->json($result, $statusCode);
    }

    /**
     * API: Lấy chi tiết major
     */
    public function showMajor(int $majorId): JsonResponse
    {
        $major = $this->majorService->getMajorDetail($majorId);

        if (!$major) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy Chuyên ngành'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $major
        ]);
    }

    /**
     * API: Cập nhật major
     */
    public function updateMajor(UpdateMajorRequest $request, int $majorId): JsonResponse
    {
        $result = $this->majorService->updateMajor($majorId, $request->validated());

        $statusCode = $result['success'] ? 200 : 422;

        return response()->json($result, $statusCode);
    }

    /**
     * API: Xóa major
     */
    public function deleteMajor(int $majorId): JsonResponse
    {
        $result = $this->majorService->deleteMajor($majorId);

        $statusCode = $result['success'] ? 200 : 422;

        return response()->json($result, $statusCode);
    }

    // ==================== COURSE APIs ====================

    /**
     * API: Lấy danh sách courses
     */
    public function getCourses(Request $request): JsonResponse
    {
        $filters = [
            'keyword' => $request->get('keyword'),
            'faculty_id' => $request->get('faculty_id'),
            'major_id' => $request->get('major_id'),
            'show_locked' => true, // Admin thấy tất cả
        ];

        $perPage = $request->get('per_page', 10);

        $courses = $this->courseService->getCourses($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'courses' => $courses->items(),
                'pagination' => [
                    'current_page' => $courses->currentPage(),
                    'last_page' => $courses->lastPage(),
                    'per_page' => $courses->perPage(),
                    'total' => $courses->total(),
                    'from' => $courses->firstItem(),
                    'to' => $courses->lastItem()
                ]
            ]
        ]);
    }

    /**
     * API: Tạo course mới
     */
    public function storeCourse(StoreCourseRequest $request): JsonResponse
    {
        $result = $this->courseService->createCourse($request->validated());

        $statusCode = $result['success'] ? 201 : 422;

        return response()->json($result, $statusCode);
    }

    /**
     * API: Lấy chi tiết course
     */
    public function showCourse(int $courseId): JsonResponse
    {
        $course = $this->courseService->getCourseDetail($courseId);

        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy Học phần'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $course
        ]);
    }

    /**
     * API: Cập nhật course
     */
    public function updateCourse(UpdateCourseRequest $request, int $courseId): JsonResponse
    {
        $result = $this->courseService->updateCourse($courseId, $request->validated());

        $statusCode = $result['success'] ? 200 : 422;

        return response()->json($result, $statusCode);
    }

    /**
     * API: Xóa course
     */
    public function deleteCourse(int $courseId): JsonResponse
    {
        $result = $this->courseService->deleteCourse($courseId);

        $statusCode = $result['success'] ? 200 : 422;

        return response()->json($result, $statusCode);
    }

    /**
     * API: Toggle lock course
     */
    public function toggleCourseLock(int $courseId): JsonResponse
    {
        $result = $this->courseService->toggleLock($courseId);

        $statusCode = $result['success'] ? 200 : 422;

        return response()->json($result, $statusCode);
    }

    // ==================== HELPER APIs ====================

    /**
     * API: Lấy tất cả faculties active (cho dropdown)
     */
    public function getActiveFaculties(): JsonResponse
    {
        $faculties = $this->facultyService->getAllActive();

        return response()->json([
            'success' => true,
            'data' => $faculties
        ]);
    }

    /**
     * API: Lấy tất cả majors active (cho dropdown)
     */
    public function getActiveMajors(): JsonResponse
    {
        $majors = $this->majorService->getAllActive();

        return response()->json([
            'success' => true,
            'data' => $majors
        ]);
    }

    /**
     * API: Lấy tất cả grading schemes active (cho dropdown)
     */
    public function getActiveGradingSchemes(): JsonResponse
    {
        $gradingSchemes = \App\Models\GradingScheme::where('status_id', 1)
            ->select('grading_scheme_id', 'scheme_code', 'scheme_name')
            ->orderBy('scheme_name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $gradingSchemes
        ]);
    }

    /**
     * API: Kiểm tra mã học phần có tồn tại không và gợi ý mã khác
     */
    public function checkCourseCode(string $code): JsonResponse
    {
        $code = strtoupper(trim($code));
        $exists = $this->courseService->checkCodeExists($code);

        if ($exists) {
            // Gợi ý mã mới bằng cách thêm số vào cuối
            $suggestions = [];
            $baseCode = preg_replace('/\d+$/', '', $code); // Loại bỏ số ở cuối
            $baseNumber = preg_replace('/^[A-Z]+/', '', $code); // Lấy phần số
            
            if ($baseNumber === '') {
                // Nếu không có số, thêm số từ 101
                $startNum = 101;
            } else {
                // Nếu có số, tăng lên
                $startNum = intval($baseNumber) + 1;
            }

            // Tạo 3 gợi ý
            for ($i = 0; $i < 3; $i++) {
                $suggestedCode = $baseCode . ($startNum + $i);
                if (!$this->courseService->checkCodeExists($suggestedCode)) {
                    $suggestions[] = $suggestedCode;
                }
                if (count($suggestions) >= 3) break;
            }

            return response()->json([
                'exists' => true,
                'message' => 'Mã học phần đã tồn tại',
                'suggestions' => $suggestions
            ]);
        }

        return response()->json([
            'exists' => false,
            'message' => 'Mã học phần khả dụng'
        ]);
    }
}
