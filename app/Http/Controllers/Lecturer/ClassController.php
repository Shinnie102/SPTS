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
        
        // Lấy lớp được chọn từ session hoặc request
        $selectedClassId = $request->input('selected_class') ?? session('selected_class_id');
        
        // Query để lấy lớp học phần với số sinh viên
        $query = ClassSection::with([
                'courseVersion.course',
                'status',
                'semester',
            ])
            ->withCount([
                'enrollments as valid_enrollments_count' => function($query) {
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
        
        // Lấy lớp hiện tại nếu có
        $currentClass = null;
        if ($selectedClassId) {
            $currentClass = ClassSection::with(['courseVersion.course'])
                ->where('class_section_id', $selectedClassId)
                ->where('lecturer_id', $lecturerId)
                ->first();
        }
        
        // Nếu không có lớp nào được chọn, lấy lớp đầu tiên
        if (!$currentClass && $classes->count() > 0) {
            $currentClass = $classes->first();
            if ($currentClass) {
                session(['selected_class_id' => $currentClass->class_section_id]);
            }
        }
        
        return view('lecturer.classes', compact('classes', 'currentClass'));
    }
    
    /**
     * Hiển thị trang điểm danh
     */
    public function attendance($id)
    {
        $lecturerId = Auth::id();

        logger("Attendance requested for class ID: {$id}, Lecturer ID: {$lecturerId}");
        
        // Lưu lớp đã chọn vào session
        session(['selected_class_id' => $id]);
        
        // Lấy lớp hiện tại
        $currentClass = ClassSection::with(['courseVersion.course'])
            ->where('class_section_id', $id)
            ->where('lecturer_id', $lecturerId)
            ->firstOrFail();
        
        // Lấy tất cả lớp của giảng viên cho dropdown
        $classes = ClassSection::with(['courseVersion.course'])
            ->where('lecturer_id', $lecturerId)
            ->orderBy('class_section_id', 'desc')
            ->get();
        
        return view('lecturer.attendance', compact('currentClass', 'classes'));
    }
    
    /**
     * Hiển thị trang nhập điểm
     */
    public function grading($id)
    {
        $lecturerId = Auth::id();
        
        // Lưu lớp đã chọn vào session
        session(['selected_class_id' => $id]);
        
        $currentClass = ClassSection::with(['courseVersion.course'])
            ->where('class_section_id', $id)
            ->where('lecturer_id', $lecturerId)
            ->firstOrFail();
        
        $classes = ClassSection::with(['courseVersion.course'])
            ->where('lecturer_id', $lecturerId)
            ->orderBy('class_section_id', 'desc')
            ->get();
        
        return view('lecturer.grading', compact('currentClass', 'classes'));
    }
    
    /**
     * Hiển thị trang trạng thái lớp
     */
    public function status($id)
    {
        $lecturerId = Auth::id();
        
        // Lưu lớp đã chọn vào session
        session(['selected_class_id' => $id]);
        
        $currentClass = ClassSection::with(['courseVersion.course'])
            ->where('class_section_id', $id)
            ->where('lecturer_id', $lecturerId)
            ->firstOrFail();
        
        $classes = ClassSection::with(['courseVersion.course'])
            ->where('lecturer_id', $lecturerId)
            ->orderBy('class_section_id', 'desc')
            ->get();
        
        return view('lecturer.classStatus', compact('currentClass', 'classes'));
    }
    
    /**
     * Hiển thị trang báo cáo
     */
    public function report($id)
    {
        $lecturerId = Auth::id();
        
        // Lưu lớp đã chọn vào session
        session(['selected_class_id' => $id]);
        
        $currentClass = ClassSection::with(['courseVersion.course'])
            ->where('class_section_id', $id)
            ->where('lecturer_id', $lecturerId)
            ->firstOrFail();
        
        $classes = ClassSection::with(['courseVersion.course'])
            ->where('lecturer_id', $lecturerId)
            ->orderBy('class_section_id', 'desc')
            ->get();
        
        return view('lecturer.report', compact('currentClass', 'classes'));
    }
}