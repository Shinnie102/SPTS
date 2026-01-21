<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\Lecturer\CalculationService;
use App\Services\Lecturer\GradingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GradingController extends Controller
{
    /**
     * Hiển thị trang nhập điểm
     */
    public function grading($id, GradingService $gradingService)
    {
        $lecturerId = Auth::id();

        // Lưu lớp đã chọn vào session
        session(['selected_class_id' => $id]);

        $viewData = $gradingService->getGradingPageData((int) $id, (int) $lecturerId);
        return view('lecturer.grading', $viewData);
    }

    /**
     * API: Lấy dữ liệu cấu trúc điểm + bảng điểm cho lớp
     */
    public function getGradingData($id, GradingService $gradingService)
    {
        $lecturerId = Auth::id();

        [$status, $payload] = $gradingService->getGradingData((int) $id, (int) $lecturerId);
        return response()->json($payload, $status);
    }

    /**
     * API: Lưu cấu trúc điểm và/hoặc bảng điểm
     */
    public function saveGrading(Request $request, $id, GradingService $gradingService)
    {
        $lecturerId = Auth::id();

        [$status, $payload] = $gradingService->saveGrading($request, (int) $id, (int) $lecturerId);
        return response()->json($payload, $status);
    }

    /**
     * Khóa điểm (stub)
     */
    public function lockGrades($id, GradingService $gradingService)
    {
        $lecturerId = Auth::id();

        [$status, $payload] = $gradingService->lockGrades((int) $id, (int) $lecturerId);
        return response()->json($payload, $status);
    }
}
