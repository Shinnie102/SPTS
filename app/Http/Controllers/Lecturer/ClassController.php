<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Services\Lecturer\ClassExportService;
use App\Services\Lecturer\ClassStatusService;
use App\Services\Lecturer\LecturerClassService;

class ClassController extends Controller
{
    /**
     * Hiển thị danh sách lớp học phần của giảng viên
     */
    public function index(Request $request, LecturerClassService $lecturerClassService)
    {
        $lecturerId = Auth::id();
        
        // Xác thực người dùng là giảng viên
        $user = User::find($lecturerId);
        if (!$user || !$user->isLecturer()) {
            abort(403, 'Bạn không có quyền truy cập trang này');
        }
        
        $selectedClassId = $request->input('selected_class') ?? session('selected_class_id');
        $selectedClassId = $selectedClassId !== null ? (int) $selectedClassId : null;

        $searchTerm = $request->has('search') ? (string) $request->input('search') : null;

        $viewData = $lecturerClassService->getIndexViewData((int) $lecturerId, $selectedClassId, $searchTerm, 15);

        if (!empty($viewData['recommendedSelectedClassId'])) {
            session(['selected_class_id' => $viewData['recommendedSelectedClassId']]);
        }

        return view('lecturer.classes', [
            'classes' => $viewData['classes'],
            'currentClass' => $viewData['currentClass'],
        ]);
    }

    /**
     * Chi tiết lớp học phần (route: /lecturer/class/{id})
     */
    public function show($id, LecturerClassService $lecturerClassService)
    {
        $lecturerId = Auth::id();

        // Lưu lớp đã chọn vào session
        session(['selected_class_id' => $id]);

        $viewData = $lecturerClassService->getShowViewData((int) $id, (int) $lecturerId);
        return view('lecturer.classDetail', $viewData);
    }
    
    /**
     * Hiển thị trang trạng thái lớp
     */
    public function status($id, ClassStatusService $classStatusService)
    {
        $lecturerId = Auth::id();

        // Lưu lớp đã chọn vào session
        session(['selected_class_id' => $id]);

        $viewData = $classStatusService->getViewData((int) $id, (int) $lecturerId);
        $viewData['dashboard']['updated_by'] = Auth::user()?->full_name ?? Auth::user()?->name ?? '—';

        return view('lecturer.classStatus', $viewData);
    }

    /**
     * Xuất bảng điểm (Excel/PDF)
     * GET /lecturer/class/{id}/export-scores?type=excel|pdf
     */
    public function exportScores(Request $request, $id, ClassExportService $classExportService)
    {
        $lecturerId = Auth::id();

        return $classExportService->exportScores($request, (int) $id, (int) $lecturerId);
    }
    
}