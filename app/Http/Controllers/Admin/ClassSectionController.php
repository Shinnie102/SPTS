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
     * API: Lấy options cho form tạo lớp (bước 1)
     */
    public function getCreateOptions(): JsonResponse
    {
        try {
            $options = $this->classService->getCreateOptions();
            return response()->json(['success' => true, 'data' => $options]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy options tạo lớp: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Lấy danh sách học kỳ theo năm học
     */
    public function getSemestersByYear(Request $request): JsonResponse
    {
        $yearId = $request->get('academic_year_id');
        if (!$yearId) {
            return response()->json(['success' => false, 'message' => 'Thiếu academic_year_id'], 400);
        }

        try {
            $semesters = $this->classService->getSemestersByAcademicYear((int) $yearId);
            return response()->json(['success' => true, 'data' => $semesters]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Lấy danh sách học phần theo chuyên ngành
     */
    public function getCoursesByMajor(Request $request): JsonResponse
    {
        $majorId = $request->get('major_id');
        if (!$majorId) {
            return response()->json(['success' => false, 'message' => 'Thiếu major_id'], 400);
        }

        try {
            $courses = $this->classService->getCoursesByMajor((int) $majorId);
            return response()->json(['success' => true, 'data' => $courses]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Lưu bước 1 vào session
     */
    public function storeStepOne(Request $request): JsonResponse
    {
        $request->validate([
            'class_code' => 'required|string|max:50',
            'course_version_id' => 'required|integer',
            'semester_id' => 'required|integer',
            'capacity' => 'required|integer|min:1',
            'time_slot_id' => 'required|integer',
            'room_id' => 'required|integer',
            'meeting_dates' => 'required|array|min:1',
            'meeting_dates.*' => 'date',
            'academic_year_id' => 'nullable|integer',
            'faculty_id' => 'nullable|integer',
            'major_id' => 'nullable|integer',
        ]);

        try {
            if ($this->classService->getClassCodeExists($request->get('class_code'))) {
                return response()->json(['success' => false, 'message' => 'Mã lớp đã tồn tại.'], 422);
            }

            $payload = $this->classService->storeStepOne($request->all());
            return response()->json(['success' => true, 'data' => $payload]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể lưu bước 1: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Lấy dữ liệu đã lưu của bước 1
     */
    public function getStepOne(): JsonResponse
    {
        $data = $this->classService->getStepOne();
        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * API: Danh sách giảng viên
     */
    public function lecturers(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->classService->getLecturers(),
        ]);
    }

    /**
     * API: Danh sách sinh viên
     */
    public function students(Request $request): JsonResponse
    {
        $keyword = $request->get('keyword');
        return response()->json([
            'success' => true,
            'data' => $this->classService->getStudents($keyword),
        ]);
    }

    /**
     * API: Danh sách khoa
     */
    public function faculties(): JsonResponse
    {
        try {
            $faculties = $this->classService->getFaculties();
            return response()->json([
                'success' => true,
                'data' => $faculties,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy khoa: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Hoàn tất tạo lớp (bước 2)
     */
    public function finalize(Request $request): JsonResponse
    {
        $request->validate([
            'lecturer_id' => 'required|integer',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'integer'
        ]);

        try {
            $result = $this->classService->createWithEnrollments($request->all());
            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Tạo lớp học thành công',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * API: Lấy danh sách chuyên ngành theo khoa
     */
    public function getMajorsByFaculty(Request $request): JsonResponse
    {
        try {
            $facultyId = $request->get('faculty_id');
            // Nếu facultyId trống hoặc 0, lấy tất cả majors
            $facultyId = !empty($facultyId) ? (int) $facultyId : 0;
            
            $majors = app(\App\Repositories\ClassSectionRepository::class)->getMajorsByFaculty($facultyId);

            return response()->json([
                'success' => true,
                'data' => [
                    'majors' => $majors,
                ],
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
     * API: Cập nhật thông tin lớp học (bước 1)
     * 
     * @param int $id ID của lớp học
     * @param Request $request
     * @return JsonResponse
     */
    public function updateStepOne(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'class_code' => 'required|string|max:50',
            'course_version_id' => 'required|integer',
            'semester_id' => 'required|integer',
            'capacity' => 'required|integer|min:1',
            'time_slot_id' => 'required|integer',
            'room_id' => 'required|integer',
            'meeting_dates' => 'required|array|min:1',
            'meeting_dates.*' => 'date',
        ]);

        try {
            // Check if class code exists for other classes
            $existingClass = $this->classService->getClassCodeExistsForUpdate($request->get('class_code'), $id);
            if ($existingClass) {
                return response()->json(['success' => false, 'message' => 'Mã lớp đã tồn tại.'], 422);
            }

            $result = $this->classService->updateStepOne($id, $request->all());
            return response()->json(['success' => true, 'data' => $result, 'message' => 'Cập nhật thông tin lớp học thành công']);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể cập nhật: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Cập nhật thành viên lớp học (bước 2)
     * 
     * @param int $id ID của lớp học
     * @param Request $request
     * @return JsonResponse
     */
    public function updateStepTwo(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'lecturer_id' => 'required|integer',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'integer'
        ]);

        try {
            $result = $this->classService->updateStepTwo($id, $request->all());
            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Cập nhật thành viên lớp học thành công',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
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

    /**
     * API: Xóa một enrollment khỏi lớp
     */
    public function deleteEnrollment(int $id): JsonResponse
    {
        try {
            $this->classService->deleteEnrollment($id);
            return response()->json([
                'success' => true,
                'message' => 'Đã xóa sinh viên khỏi lớp',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
