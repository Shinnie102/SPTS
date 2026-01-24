<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $notifications = [];

        /*
        |--------------------------------------------------------------------------
        | 1️⃣ PHÂN CÔNG LỚP THÀNH CÔNG
        | Điều kiện: lớp có ít nhất 1 class_meeting
        |--------------------------------------------------------------------------
        */
        $assignedClasses = DB::table('class_section as cs')
            ->join('class_meeting as cm', 'cm.class_section_id', '=', 'cs.class_section_id')
            ->select('cs.class_code', 'cm.created_at')
            ->orderByDesc('cm.created_at')
            ->limit(3)
            ->get();

        foreach ($assignedClasses as $class) {
            $notifications[] = [
                'type' => 'success',
                'title' => 'Phân công lớp thành công',
                'message' => "Lớp {$class->class_code} đã được phân công giảng dạy",
                'time' => $class->created_at,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 2️⃣ HỌC KỲ MỚI ĐƯỢC TẠO (ĐÚNG THEO BẢNG semester)
        |--------------------------------------------------------------------------
        */
        $newSemesters = DB::table('semester')
            ->orderByDesc('created_at')
            ->limit(2)
            ->get();

        foreach ($newSemesters as $sem) {
            $notifications[] = [
                'type' => 'warning',
                'title' => 'Học kỳ mới được tạo',
                'message' => "Học kỳ {$sem->semester_code}",
                'time' => $sem->created_at,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 3️⃣ CÓ LỖI DỮ LIỆU / THIẾU PHÂN CÔNG
        | Điều kiện: lớp chưa có class_meeting
        |--------------------------------------------------------------------------
        */
        $missingAssignClasses = DB::table('class_section as cs')
            ->leftJoin('class_meeting as cm', 'cm.class_section_id', '=', 'cs.class_section_id')
            ->whereNull('cm.class_meeting_id')
            ->select('cs.class_code')
            ->limit(3)
            ->get();

        foreach ($missingAssignClasses as $class) {
            $notifications[] = [
                'type' => 'danger',
                'title' => 'Có lỗi dữ liệu / thiếu phân công',
                'message' => "Lớp {$class->class_code} chưa được phân công giảng dạy",
                'time' => now(),
            ];
        }

        return view('admin.adminDashboard', compact('notifications'));
    }
}
