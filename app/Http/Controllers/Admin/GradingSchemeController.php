<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GradingSchemeService;
use App\Services\AcademicRuleService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Class GradingSchemeController
 * 
 * Controller xử lý quản lý sơ đồ điểm và quy tắc học vụ
 * Tuân thủ Single Responsibility Principle (S in SOLID)
 */
class GradingSchemeController extends Controller
{
    /**
     * @var GradingSchemeService
     */
    protected $gradingSchemeService;

    /**
     * @var AcademicRuleService
     */
    protected $academicRuleService;

    /**
     * Constructor - Dependency Injection
     *
     * @param GradingSchemeService $gradingSchemeService
     * @param AcademicRuleService $academicRuleService
     */
    public function __construct(
        GradingSchemeService $gradingSchemeService,
        AcademicRuleService $academicRuleService
    ) {
        $this->gradingSchemeService = $gradingSchemeService;
        $this->academicRuleService = $academicRuleService;
    }

    /**
     * Hiển thị trang quản lý quy tắc đánh giá
     *
     * @return View
     */
    public function index(): View
    {
        return view('admin.adminQuytac');
    }

    /**
     * API: Lấy tất cả dữ liệu (quy tắc học vụ + sơ đồ điểm)
     *
     * @return JsonResponse
     */
    public function getData(): JsonResponse
    {
        try {
            $academicRules = $this->academicRuleService->getFormattedRules();
            $gradingSchemes = $this->gradingSchemeService->getAllGradingSchemes();

            return response()->json([
                'success' => true,
                'data' => [
                    'academic_rules' => $academicRules,
                    'grading_schemes' => $gradingSchemes
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tải dữ liệu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Lấy danh sách sơ đồ điểm
     *
     * @return JsonResponse
     */
    public function getGradingSchemes(): JsonResponse
    {
        try {
            $gradingSchemes = $this->gradingSchemeService->getAllGradingSchemes();

            return response()->json([
                'success' => true,
                'data' => $gradingSchemes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Lấy chi tiết sơ đồ điểm
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $gradingScheme = $this->gradingSchemeService->getGradingSchemeDetail($id);

            if (!$gradingScheme) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy sơ đồ điểm'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $gradingScheme
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Tạo sơ đồ điểm mới
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // Validate request
        $request->validate([
            'scheme_code' => 'required|string|max:50',
            'scheme_name' => 'required|string|max:150',
            'description' => 'nullable|string|max:255',
            'components' => 'required|array|min:1',
            'components.*.component_name' => 'required|string|max:150',
            'components.*.weight_percent' => 'required|numeric|min:0|max:100',
        ], [
            'scheme_code.required' => 'Mã sơ đồ là bắt buộc',
            'scheme_name.required' => 'Tên sơ đồ là bắt buộc',
            'components.required' => 'Phải có ít nhất một thành phần điểm',
            'components.*.component_name.required' => 'Tên thành phần điểm là bắt buộc',
            'components.*.weight_percent.required' => 'Trọng số là bắt buộc',
        ]);

        $result = $this->gradingSchemeService->createGradingScheme($request->all());

        return response()->json($result, $result['success'] ? 201 : 400);
    }

    /**
     * API: Cập nhật sơ đồ điểm
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        // Validate request
        $request->validate([
            'scheme_code' => 'required|string|max:50',
            'scheme_name' => 'required|string|max:150',
            'description' => 'nullable|string|max:255',
            'components' => 'required|array|min:1',
            'components.*.component_name' => 'required|string|max:150',
            'components.*.weight_percent' => 'required|numeric|min:0|max:100',
        ], [
            'scheme_code.required' => 'Mã sơ đồ là bắt buộc',
            'scheme_name.required' => 'Tên sơ đồ là bắt buộc',
            'components.required' => 'Phải có ít nhất một thành phần điểm',
            'components.*.component_name.required' => 'Tên thành phần điểm là bắt buộc',
            'components.*.weight_percent.required' => 'Trọng số là bắt buộc',
        ]);

        $result = $this->gradingSchemeService->updateGradingScheme($id, $request->all());

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * API: Xóa sơ đồ điểm
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->gradingSchemeService->deleteGradingScheme($id);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * API: Lấy danh sách quy tắc học vụ
     *
     * @return JsonResponse
     */
    public function getAcademicRules(): JsonResponse
    {
        try {
            $rules = $this->academicRuleService->getFormattedRules();

            return response()->json([
                'success' => true,
                'data' => $rules
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
}
