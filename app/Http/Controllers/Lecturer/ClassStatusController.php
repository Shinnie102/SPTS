<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Services\Lecturer\ClassStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClassStatusController extends Controller
{
    /**
     * GET /lecturer/class/{id}/status
     * Keep route name: lecturer.class.status
     */
    public function show(Request $request, $id, ClassStatusService $classStatusService)
    {
        $lecturerId = Auth::id();

        // Lưu lớp đã chọn vào session (existing behavior)
        session(['selected_class_id' => $id]);

        $viewData = $classStatusService->getViewData((int) $id, (int) $lecturerId);

        // Preserve dashboard.updated_by behavior from old controller
        $viewData['dashboard']['updated_by'] = Auth::user()?->full_name ?? Auth::user()?->name ?? '—';

        return view('lecturer.classStatus', $viewData);
    }

    /**
     * POST /lecturer/class/{id}/status/lock
     * Lock class data by moving class_section_status to COMPLETED.
     */
    public function lock(Request $request, $id, ClassStatusService $classStatusService)
    {
        $lecturerId = Auth::id();

        [$status, $payload] = $classStatusService->lockClass((int) $id, (int) $lecturerId);
        return response()->json($payload, $status);
    }
}
