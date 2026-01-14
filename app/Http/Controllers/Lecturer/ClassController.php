<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ClassSection;
use App\Models\User;

class ClassController extends Controller
{
    /**
     * Hiển thị danh sách lớp học phần của giảng viên
     */
    public function index(Request $request)
    {
        $lecturerId = Auth::id();
        
        // Xác thực người dùng là giảng viên
        $user = User::find($lecturerId);
        if (!$user || !$user->isLecturer()) {
            abort(403, 'Bạn không có quyền truy cập trang này');
        }
        
        // Query để lấy lớp học phần với số sinh viên (sử dụng withCount để tối ưu)
        $query = ClassSection::with([
                'courseVersion.course',
                'status',
                'semester',
            ])
            ->withCount([
                'enrollments as valid_enrollments_count' => function($query) {
                    // Chỉ đếm enrollment có trạng thái hợp lệ
                    $query->whereIn('enrollment_status_id', [1, 2]);
                }
            ])
            ->where('lecturer_id', $lecturerId);
        
        // Tìm kiếm nếu có
        if ($request->has('search') && $request->search != '') {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('class_code', 'LIKE', "%{$searchTerm}%")
                  ->orWhereHas('courseVersion.course', function($query) use ($searchTerm) {
                      $query->where('course_code', 'LIKE', "%{$searchTerm}%")
                            ->orWhere('course_name', 'LIKE', "%{$searchTerm}%");
                  });
            });
        }
        
        // Phân trang 15 lớp mỗi trang
        $classes = $query->orderBy('class_section_id', 'desc')->paginate(15);
        
        return view('lecturer.classes', compact('classes'));
    }
    
    /**
     * Hiển thị chi tiết lớp học phần
     */
    public function show($id)
    {
        $lecturerId = Auth::id();
        
        $class = ClassSection::with([
                'courseVersion.course',
                'status',
                'semester.academicYear',
                'enrollments' => function($query) {
                    $query->whereIn('enrollment_status_id', [1, 2])
                          ->with(['student', 'attendances']);
                }
            ])
            ->withCount([
                'enrollments as valid_enrollments_count' => function($query) {
                    $query->whereIn('enrollment_status_id', [1, 2]);
                }
            ])
            ->where('class_section_id', $id)
            ->where('lecturer_id', $lecturerId)
            ->firstOrFail();
        
        return view('lecturer.classDetail', compact('class'));
    }
    
    /**
     * Hiển thị trang nhập điểm
     */
    public function grading($id)
    {
        $lecturerId = Auth::id();
        
        $class = ClassSection::with([
                'courseVersion.course',
                'enrollments' => function($query) {
                    $query->whereIn('enrollment_status_id', [1, 2])
                          ->with(['student', 'studentScores.gradingComponent']);
                },
                'gradingSchemes.gradingScheme.components'
            ])
            ->where('class_section_id', $id)
            ->where('lecturer_id', $lecturerId)
            ->firstOrFail();
        
        return view('lecturer.grading', compact('class'));
    }
}