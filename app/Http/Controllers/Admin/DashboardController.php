<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ClassSection;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.adminDashboard');
    }

    /**
     * API: Lấy dữ liệu dashboard (DB thật)
     */
    public function getDashboardData()
    {
        /* =========================
         * 1. OVERVIEW
         * ========================= */

        $totalUsers = User::count();

        $totalClasses = ClassSection::count();

        $warningStudents = Enrollment::whereIn('enrollment_status_id', [1, 2])
            ->distinct('student_id')
            ->count('student_id');

        /* =========================
         * 2. DANH SÁCH LỚP CÓ VẤN ĐỀ
         * ========================= */

        $problemClassSections = ClassSection::with([
                'courseVersion.course'
            ])
            ->withCount([
                'enrollments as student_count' => function ($q) {
                    $q->whereIn('enrollment_status_id', [1, 2]);
                }
            ])
            ->having('student_count', '>', 50)
            ->get();

        $problemClasses = $problemClassSections->map(function ($class) {
            return [
                'classCode'   => $class->class_code,
                'courseName' => $class->course_name,
                'issueCount' => 1,
                'severity'   => 'Cao',
                'status'     => 'pending',
            ];
        });

        /* =========================
         * 3. RESPONSE JSON
         * ========================= */

        return response()->json([
            'overview' => [
                'totalUsers' => [
                    'value' => $totalUsers,
                    'description' => 'Toàn hệ thống'
                ],
                'totalClasses' => [
                    'value' => $totalClasses,
                    'description' => 'Tổng lớp học phần'
                ],
                'warningStudents' => [
                    'value' => $warningStudents,
                    'description' => 'Sinh viên đang học'
                ],
                'problemClasses' => [
                    'value' => $problemClasses->count(),
                    'description' => 'Lớp quá tải'
                ],
            ],

            'systemAlerts' => [
                'totalIssues' => $problemClasses->count(),
                'alerts' => []
            ],

            'problemDistribution' => [
                'labels' => ['Quá tải'],
                'values' => [$problemClasses->count()],
                'colors' => ['#ef4444']
            ],

            'problemClasses' => $problemClasses
        ]);
    }
}
