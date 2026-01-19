<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\ClassSection;

class DashboardController extends Controller
{
    public function index()
    {
        $lecturerId = Auth::id();

        // 1. Tổng số lớp phụ trách
        $totalClasses = ClassSection::where('lecturer_id', $lecturerId)->count();

        // 2. Cảnh báo (CHƯA CÓ BẢNG → 0)
        $warnings = 0;

        // 3. Lớp đã hoàn tất nhập điểm (TẠM)
        $completedGrading = 0;

        // 4. Lớp cần nhập điểm (TẠM)
        $pendingGrading = 0;

        // 5. Danh sách lớp mới nhất (3 lớp)
        $latestClasses = ClassSection::with([
                'courseVersion.course',
                'status'
            ])
            ->where('lecturer_id', $lecturerId)
            ->orderByDesc('created_at')
            ->take(3)
            ->get();

        return view('lecturer.lecturerDashboard', compact(
            'totalClasses',
            'warnings',
            'completedGrading',
            'pendingGrading',
            'latestClasses'
        ));
    }
}
