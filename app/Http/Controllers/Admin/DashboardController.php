<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.adminDashboard');
    }

    public function getDashboardData()
    {
        try {
            // 1. Tổng số người dùng
            $totalUsers = DB::table('user')->count();

            // 2. Tổng số lớp học phần
            $totalClasses = DB::table('class_section')->count();

            // 3. Số sinh viên có vấn đề
            $warningStudents = DB::table('enrollment')
                ->where('enrollment_status_id', 2)
                ->count();

            // 4. Lớp học phần chưa có buổi học nào
            $classesWithoutMeeting = DB::table('class_section as cs')
                ->join('course_version as cv', 'cv.course_version_id', '=', 'cs.course_version_id')
                ->join('course as c', 'c.course_id', '=', 'cv.course_id')
                ->leftJoin('class_meeting as cm', 'cm.class_section_id', '=', 'cs.class_section_id')
                ->whereNull('cm.class_meeting_id')
                ->select(
                    'cs.class_code',
                    'c.course_name'
                )
                ->get();

            $problemClasses = $classesWithoutMeeting->count();

            // 5. Format danh sách lớp có vấn đề
            $problemClassesList = $classesWithoutMeeting->map(function($class) {
                return [
                    'class_code' => $class->class_code,
                    'course_name' => $class->course_name,
                    'problem_count' => 1
                ];
            });

            // 6. Phân tích nguyên nhân
            $problemCauses = [
                'Chưa có buổi học' => $problemClasses,
                'Thiếu điểm danh' => 0,
                'Thiếu điểm' => 0
            ];

            return response()->json([
                'error' => false,
                'cards' => [
                    'totalUsers' => $totalUsers,
                    'totalClasses' => $totalClasses,
                    'warningStudents' => $warningStudents,
                    'problemClasses' => $problemClasses
                ],
                'problemClassesList' => $problemClassesList,
                'problemCauses' => $problemCauses
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
