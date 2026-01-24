<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\Student\Score\StudentScoreService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Controller xử lý trang học tập của sinh viên
 */
class StudentStudyController extends Controller
{
    /**
     * Student Score Service instance
     *
     * @var StudentScoreService
     */
    protected $scoreService;

    /**
     * Constructor - Dependency Injection
     *
     * @param StudentScoreService $scoreService
     */
    public function __construct(StudentScoreService $scoreService)
    {
        $this->scoreService = $scoreService;
    }

    /**
     * Hiển thị trang học tập
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                Log::error('User not authenticated in StudentStudyController');
                return redirect()->route('login');
            }

            // Lấy dữ liệu điểm từ Service
            $scoreData = $this->scoreService->getStudentScores($user->user_id);

            // Log debug
            if (config('app.debug')) {
                Log::info('Student scores loaded', [
                    'student_id' => $user->user_id,
                    'gpa' => $scoreData['summary']['gpa'],
                    'semesters_count' => count($scoreData['semesters'])
                ]);
            }

            return view('student.studentStudy', compact('scoreData'));

        } catch (\Exception $e) {
            Log::error('Error loading student scores', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Return view với data trống
            return view('student.studentStudy', [
                'scoreData' => [
                    'summary' => [
                        'gpa' => 0,
                        'total_credits' => 0,
                        'passed_credits' => 0,
                        'progress' => 0
                    ],
                    'semesters' => []
                ]
            ]);
        }
    }
}
