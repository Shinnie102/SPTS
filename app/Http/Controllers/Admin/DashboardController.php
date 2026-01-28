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

            // 7. Tính toán thống kê
            $classWarningPercentage = $totalClasses > 0 ? round(($problemClasses / $totalClasses) * 100, 1) : 0;
            $studentWarningPercentage = $totalUsers > 0 ? round(($warningStudents / $totalUsers) * 100, 1) : 0;
            $totalIssues = $problemClasses + $warningStudents;

            // 8. Tạo system warnings
            $systemWarnings = $this->generateSystemWarnings(
                $problemClasses,
                $warningStudents,
                $classWarningPercentage,
                $studentWarningPercentage,
                $totalIssues
            );

            return response()->json([
                'error' => false,
                'cards' => [
                    'totalUsers' => $totalUsers,
                    'totalClasses' => $totalClasses,
                    'warningStudents' => $warningStudents,
                    'problemClasses' => $problemClasses
                ],
                'problemClassesList' => $problemClassesList,
                'problemCauses' => $problemCauses,
                'systemWarnings' => $systemWarnings,
                'statistics' => [
                    'classWarningPercentage' => $classWarningPercentage,
                    'studentWarningPercentage' => $studentWarningPercentage,
                    'totalIssues' => $totalIssues
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tạo danh sách cảnh báo hệ thống
     */
    private function generateSystemWarnings($problemClasses, $warningStudents, $classWarningPercentage, $studentWarningPercentage, $totalIssues)
    {
        $warnings = [];

        // Cảnh báo nghiêm trọng về tỷ lệ lớp
        if ($classWarningPercentage > 20) {
            $warnings[] = [
                'type' => 'critical',
                'icon' => '🚨',
                'title' => 'Tỷ lệ lớp có vấn đề cao',
                'message' => "{$classWarningPercentage}% tổng số lớp đang có vấn đề cần xử lý khẩn cấp",
                'count' => $classWarningPercentage,
                'priority' => 'critical'
            ];
        }

        // Cảnh báo về lớp học
        if ($problemClasses > 0) {
            $warnings[] = [
                'type' => 'error',
                'icon' => '📚',
                'title' => 'Lớp học chưa có buổi học',
                'message' => "Có {$problemClasses} lớp học phần chưa có buổi học nào được lên lịch",
                'count' => $problemClasses,
                'priority' => 'high'
            ];
        }

        // Cảnh báo về sinh viên
        if ($warningStudents > 0) {
            $warnings[] = [
                'type' => 'warning',
                'icon' => '⚠️',
                'title' => 'Sinh viên có vấn đề',
                'message' => "Có {$warningStudents} sinh viên đang trong trạng thái cảnh báo học vụ",
                'count' => $warningStudents,
                'priority' => 'high'
            ];
        }

        // Cảnh báo nghiêm trọng về tỷ lệ sinh viên
        if ($studentWarningPercentage > 30) {
            $warnings[] = [
                'type' => 'critical',
                'icon' => '🔴',
                'title' => 'Tỷ lệ sinh viên cảnh báo cao',
                'message' => "{$studentWarningPercentage}% sinh viên đang trong tình trạng học vụ không tốt",
                'count' => $studentWarningPercentage,
                'priority' => 'critical'
            ];
        }

        // Cảnh báo tổng quan
        if ($totalIssues > 0) {
            $warnings[] = [
                'type' => 'info',
                'icon' => 'ℹ️',
                'title' => 'Tổng quan vấn đề',
                'message' => "Hệ thống phát hiện tổng cộng {$totalIssues} vấn đề cần được xử lý",
                'count' => $totalIssues,
                'priority' => 'medium'
            ];
        }

        // Thêm cảnh báo về deadline (tùy chọn - có thể customize)
        if ($problemClasses > 10) {
            $warnings[] = [
                'type' => 'warning',
                'icon' => '⏰',
                'title' => 'Cần hành động ngay',
                'message' => "Số lượng lớp chưa có lịch học đang tăng cao, cần xử lý trong 48 giờ",
                'count' => $problemClasses,
                'priority' => 'high'
            ];
        }

        // Sắp xếp theo mức độ ưu tiên
        usort($warnings, function($a, $b) {
            $priority = ['critical' => 1, 'high' => 2, 'medium' => 3, 'low' => 4];
            return $priority[$a['priority']] - $priority[$b['priority']];
        });

        return $warnings;
    }

    /**
     * Lấy chi tiết một lớp học (tùy chọn - có thể dùng sau)
     */
    public function getClassDetails($classCode)
    {
        try {
            $classDetails = DB::table('class_section as cs')
                ->join('course_version as cv', 'cv.course_version_id', '=', 'cs.course_version_id')
                ->join('course as c', 'c.course_id', '=', 'cv.course_id')
                ->leftJoin('user as lecturer', 'lecturer.user_id', '=', 'cs.lecturer_id')
                ->where('cs.class_code', $classCode)
                ->select(
                    'cs.class_code',
                    'c.course_name',
                    'c.course_code',
                    'cs.capacity',
                    'cs.semester_id',
                    'lecturer.full_name as lecturer_name',
                    'cs.created_at'
                )
                ->first();

            if (!$classDetails) {
                return response()->json([
                    'error' => true,
                    'message' => 'Không tìm thấy lớp học'
                ], 404);
            }

            // Đếm số sinh viên trong lớp
            $studentCount = DB::table('enrollment')
                ->where('class_section_id', function($query) use ($classCode) {
                    $query->select('class_section_id')
                        ->from('class_section')
                        ->where('class_code', $classCode)
                        ->limit(1);
                })
                ->count();

            // Đếm số buổi học
            $meetingCount = DB::table('class_meeting')
                ->where('class_section_id', function($query) use ($classCode) {
                    $query->select('class_section_id')
                        ->from('class_section')
                        ->where('class_code', $classCode)
                        ->limit(1);
                })
                ->count();

            return response()->json([
                'error' => false,
                'classDetails' => $classDetails,
                'studentCount' => $studentCount,
                'meetingCount' => $meetingCount
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
