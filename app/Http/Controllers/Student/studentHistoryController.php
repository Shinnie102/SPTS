<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\Student\Common\StudentAttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class studentHistoryController extends Controller
{
    protected $attendanceService;

    /**
     * Constructor - Dependency Injection
     */
    public function __construct(StudentAttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Student attendance history page
     */
    public function history()
    {
        $user = Auth::user();
        
        // Sử dụng Service để lấy data
        $attendanceData = $this->attendanceService->getStudentAttendanceSummary($user->user_id);
        
        return view('student.studentHistory', compact('attendanceData'));
    }
}
