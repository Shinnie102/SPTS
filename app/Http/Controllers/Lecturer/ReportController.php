<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Services\Lecturer\ReportService;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    /**
     * Hiển thị trang báo cáo
     */
    public function report($id, ReportService $reportService)
    {
        $lecturerId = Auth::id();

        // Lưu lớp đã chọn vào session
        session(['selected_class_id' => $id]);

        $viewData = $reportService->getReportPageData((int) $id, (int) $lecturerId);
        return view('lecturer.report', $viewData);
    }

    /**
     * API: Dữ liệu báo cáo cho lớp học phần (Lecturer)
     * GET /lecturer/class/{class_section_id}/report-data
     */
    public function getReportData($id, ReportService $reportService)
    {
        $lecturerId = Auth::id();

        [$status, $payload] = $reportService->getReportData((int) $id, (int) $lecturerId);
        return response()->json($payload, $status);
    }

    /**
     * API: Chi tiết sinh viên trong lớp học phần (dùng cho modal Report)
     * GET /lecturer/class/{classId}/student/{studentId}/detail
     */
    public function getStudentDetail($classId, $studentId, ReportService $reportService)
    {
        $lecturerId = Auth::id();

        [$status, $payload] = $reportService->getStudentDetail((int) $classId, (int) $studentId, (int) $lecturerId);
        return response()->json($payload, $status);
    }

    /**
     * Xuất báo cáo (tạm thời trả về view)
     */
    public function exportReport($id, ReportService $reportService)
    {
        $lecturerId = Auth::id();

        $viewData = $reportService->getExportReportViewData((int) $id, (int) $lecturerId);
        return view('lecturer.reportExport', $viewData);
    }
}
