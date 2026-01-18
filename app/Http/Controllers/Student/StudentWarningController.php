<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\StudentWarningService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Class StudentWarningController
 * 
 * Controller xử lý API cảnh báo học vụ cho sinh viên
 */
class StudentWarningController extends Controller
{
    /**
     * @var StudentWarningService
     */
    protected $warningService;

    /**
     * Constructor
     *
     * @param StudentWarningService $warningService
     */
    public function __construct(StudentWarningService $warningService)
    {
        $this->warningService = $warningService;
    }

    /**
     * Lấy danh sách cảnh báo cho sinh viên đang đăng nhập
     *
     * @return JsonResponse
     */
    public function getWarnings(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Kiểm tra quyền: chỉ sinh viên mới được xem
            if (!$user || !$user->isStudent()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền truy cập chức năng này'
                ], 403);
            }

            $warnings = $this->warningService->getStudentWarnings($user->user_id);

            return response()->json([
                'success' => true,
                'data' => $warnings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy thông tin cảnh báo',
                'error' => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Lấy chi tiết cảnh báo cho một môn học
     *
     * @param int $classSectionId
     * @return JsonResponse
     */
    public function getSubjectDetail(int $classSectionId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user || !$user->isStudent()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền truy cập chức năng này'
                ], 403);
            }

            $detail = $this->warningService->getSubjectWarningDetail(
                $user->user_id,
                $classSectionId
            );

            return response()->json($detail);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy chi tiết môn học',
                'error' => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Lấy thống kê cảnh báo
     *
     * @return JsonResponse
     */
    public function getStatistics(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user || !$user->isStudent()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền truy cập chức năng này'
                ], 403);
            }

            $stats = $this->warningService->getWarningStatistics($user->user_id);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy thống kê',
                'error' => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
    }
}
