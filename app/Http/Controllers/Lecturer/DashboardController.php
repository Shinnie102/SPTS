<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\ClassSection;
use App\Services\Lecturer\LecturerDashboardDataService;

class DashboardController extends Controller
{
    public function index()
    {
        $lecturerId = Auth::id();

        // ✅ XÓA SESSION MỖI KHI VÀO DASHBOARD - SỐ THÔNG BÁO LUÔN HIỂN THỊ
        session()->forget('notifications_read');

        /* =========================
           1. TỔNG SỐ LỚP PHỤ TRÁCH
        ========================== */
        $totalClasses = ClassSection::where('lecturer_id', $lecturerId)->count();

        /* =========================
           2. NOTIFICATIONS
        ========================== */
        $notifications = [];

        /* ---------------------------------
           A. LỚP MỚI ĐƯỢC PHÂN CÔNG
           (dựa vào created_at hôm nay)
        --------------------------------- */
        $newClasses = ClassSection::where('lecturer_id', $lecturerId)
            ->whereDate('created_at', now()->toDateString())
            ->get();

        foreach ($newClasses as $class) {
            $notifications[] = [
                'type'    => 'info',
                'title'   => 'Có lớp mới được phân công',
                'message' => 'Lớp ' . $class->class_code
            ];
        }

        /* ---------------------------------
           B. ĐẾN HẠN NHẬP ĐIỂM
           (chưa có bản ghi trong student_score)
        --------------------------------- */
        $gradingDeadlineClasses = DB::table('class_section as cs')
            ->where('cs.lecturer_id', $lecturerId)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('student_score as ss')
                    ->join('enrollment as e', 'e.enrollment_id', '=', 'ss.enrollment_id')
                    ->whereRaw('e.class_section_id = cs.class_section_id');
            })
            ->get();

        foreach ($gradingDeadlineClasses as $class) {
            $notifications[] = [
                'type'    => 'warning',
                'title'   => 'Đến hạn nhập điểm',
                'message' => 'Lớp ' . $class->class_code . ' chưa nhập điểm'
            ];
        }

        /* ---------------------------------
           C. LỚP HỌC SẮP KẾT THÚC
           (≤ 2 buổi học – KHÔNG GROUP BY)
        --------------------------------- */
        $endingClasses = DB::table('class_section as cs')
            ->where('cs.lecturer_id', $lecturerId)
            ->whereRaw('(
                SELECT COUNT(*)
                FROM class_meeting cm
                WHERE cm.class_section_id = cs.class_section_id
            ) <= 2')
            ->get();

        foreach ($endingClasses as $class) {
            $notifications[] = [
                'type'    => 'danger',
                'title'   => 'Lớp học sắp kết thúc',
                'message' => 'Lớp ' . $class->class_code . ' còn ≤ 2 buổi học'
            ];
        }

        // ✅ LUÔN SET hasRead = false ĐỂ SỐ THÔNG BÁO LUÔN HIỂN THỊ
        $hasRead = false;

        /* =========================
           3. DASHBOARD DATA
        ========================== */
        $warnings = count($endingClasses);

        $completedGrading = 0; // có thể mở rộng sau
        $pendingGrading   = count($gradingDeadlineClasses);

        $latestClasses = ClassSection::where('lecturer_id', $lecturerId)
            ->orderByDesc('created_at')
            ->take(3)
            ->get();

        return view('lecturer.lecturerDashboard', compact(
            'totalClasses',
            'warnings',
            'completedGrading',
            'pendingGrading',
            'latestClasses',
            'notifications',
            'hasRead'
        ));
    }

    public function getDashboardData(LecturerDashboardDataService $dashboardDataService)
    {
        $lecturerId = Auth::id();

        $data = $dashboardDataService->getDashboardData((int) $lecturerId, 20);

        $totalClasses = ClassSection::where('lecturer_id', $lecturerId)->count();

        $pendingGrading = DB::table('class_section as cs')
            ->where('cs.lecturer_id', $lecturerId)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('student_score as ss')
                    ->join('enrollment as e', 'e.enrollment_id', '=', 'ss.enrollment_id')
                    ->whereRaw('e.class_section_id = cs.class_section_id');
            })
            ->count();

        $completedGrading = ClassSection::query()
            ->where('lecturer_id', $lecturerId)
            ->whereHas('status', function ($q) {
                $q->where('code', 'COMPLETED');
            })
            ->count();

        $distribution = $data['score_distribution'] ?? [];
        $chartData = [
            ['range' => '9-10', 'students' => (int) ($distribution['9_10'] ?? 0)],
            ['range' => '8-8.9', 'students' => (int) ($distribution['8_8_9'] ?? 0)],
            ['range' => '7-7.9', 'students' => (int) ($distribution['7_7_9'] ?? 0)],
            ['range' => '6-6.9', 'students' => (int) ($distribution['6_6_9'] ?? 0)],
            ['range' => '5-5.9', 'students' => (int) ($distribution['5_5_9'] ?? 0)],
            ['range' => '<5', 'students' => (int) ($distribution['below_5'] ?? 0)],
        ];

        return response()->json([
            'stats' => [
                'totalClasses' => (int) $totalClasses,
                'warnings' => (int) ($data['warnings_count'] ?? 0),
                'completedGrading' => (int) $completedGrading,
                'pendingGrading' => (int) $pendingGrading,
            ],
            'totalStudents' => (int) ($data['total_students'] ?? 0),
            'chartData' => $chartData,
            'warnings' => $data['academic_warnings'] ?? [],
        ]);
    }

    /**
     * Đánh dấu tất cả thông báo đã đọc
     * (Chỉ ẩn số trong phiên làm việc hiện tại)
     */
    public function markAllRead()
    {
        session()->put('notifications_read', true);
        return response()->json(['success' => true]);
    }
}
