<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ClassSection;
use App\Models\ClassMeeting;
use App\Models\Enrollment;
use App\Models\Attendance;
use App\Models\AttendanceStatus;
use App\Models\ClassGradingScheme;
use App\Models\GradingComponent;
use App\Models\StudentScore;
use App\Models\User;
use App\Services\Lecturer\CalculationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClassController extends Controller
{
    private function normalizeComponentKey(string $componentName): ?string
    {
        $name = mb_strtolower(trim($componentName));

        if ($name === '') {
            return null;
        }

        if (str_contains($name, 'chuyên cần') || str_contains($name, 'chuyen can') || str_contains($name, 'attendance')) {
            return 'attendance';
        }

        if (str_contains($name, 'giữa') || str_contains($name, 'giua') || str_contains($name, 'mid')) {
            return 'midterm';
        }

        if (str_contains($name, 'cuối') || str_contains($name, 'cuoi') || str_contains($name, 'final')) {
            return 'final';
        }

        if ($name === 'tổng' || $name === 'tong' || str_contains($name, 'total')) {
            return 'total';
        }

        return null;
    }

    private function getStudentsDatasetForClass(int $classSectionId): array
    {
        $enrollments = Enrollment::where('class_section_id', $classSectionId)
            ->whereIn('enrollment_status_id', [1, 2])
            ->with(['student'])
            ->get();

        return $enrollments
            ->map(function ($enrollment) {
                return [
                    'enrollment_id' => $enrollment->enrollment_id,
                    'student_id' => $enrollment->student_id,
                    'student_code' => $enrollment->student?->code_user,
                    'name' => $enrollment->student?->full_name,
                ];
            })
            ->values()
            ->all();
    }

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
     * Chi tiết lớp học phần (route: /lecturer/class/{id})
     */
    public function show($id)
    {
        $lecturerId = Auth::id();

        // Lưu lớp đã chọn vào session
        session(['selected_class_id' => $id]);

        $currentClass = ClassSection::with(['courseVersion.course', 'status', 'semester'])
            ->where('class_section_id', $id)
            ->where('lecturer_id', $lecturerId)
            ->firstOrFail();

        $classes = ClassSection::with(['courseVersion.course'])
            ->where('lecturer_id', $lecturerId)
            ->orderBy('class_section_id', 'desc')
            ->get();

        return view('lecturer.classDetail', compact('currentClass', 'classes'));
    }
    
    /**
     * Hiển thị trang trạng thái lớp
     */
    public function status($id, CalculationService $calculationService)
    {
        $lecturerId = Auth::id();
        
        // Lưu lớp đã chọn vào session
        session(['selected_class_id' => $id]);
        
        $currentClass = ClassSection::with(['courseVersion.course'])
            ->where('class_section_id', $id)
            ->where('lecturer_id', $lecturerId)
            ->firstOrFail();

        $currentClass->loadMissing('status');
        
        $classes = ClassSection::with(['courseVersion.course'])
            ->where('lecturer_id', $lecturerId)
            ->orderBy('class_section_id', 'desc')
            ->get();

        $students = $this->getStudentsDatasetForClass((int) $id);

        $meetingKeyColumn = Schema::hasColumn('class_meeting', 'meeting_id')
            ? 'meeting_id'
            : (Schema::hasColumn('class_meeting', 'class_meeting_id') ? 'class_meeting_id' : 'id');

        $meetingIds = ClassMeeting::where('class_section_id', $id)->pluck($meetingKeyColumn);
        $totalMeetings = $meetingIds->count();

        $markedMeetings = $totalMeetings > 0
            ? Attendance::whereIn('class_meeting_id', $meetingIds)->distinct()->count('class_meeting_id')
            : 0;

        $attendanceCompletionPercent = $totalMeetings > 0
            ? (int) round(($markedMeetings / $totalMeetings) * 100)
            : 0;

        $enrollmentIds = array_values(array_filter(array_map(
            fn ($s) => $s['enrollment_id'] ?? null,
            is_array($students) ? $students : []
        )));

        $totalStudents = count($enrollmentIds);

        $scoreValueColumn = null;
        if (Schema::hasTable('student_score')) {
            $scoreValueColumn = Schema::hasColumn('student_score', 'score')
                ? 'score'
                : (Schema::hasColumn('student_score', 'score_value') ? 'score_value' : null);
        }

        $scoredStudents = 0;
        if ($totalStudents > 0 && $scoreValueColumn) {
            $scoredStudents = DB::table('student_score')
                ->whereIn('enrollment_id', $enrollmentIds)
                ->whereNotNull($scoreValueColumn)
                ->distinct()
                ->count('enrollment_id');
        }

        $gradingCompletionPercent = $totalStudents > 0
            ? (int) round(($scoredStudents / $totalStudents) * 100)
            : 0;

        $lastAttendanceAt = $totalMeetings > 0
            ? Attendance::whereIn('class_meeting_id', $meetingIds)->max('marked_at')
            : null;

        $scoreUpdatedColumn = null;
        if (Schema::hasTable('student_score')) {
            $scoreUpdatedColumn = Schema::hasColumn('student_score', 'recorded_at')
                ? 'recorded_at'
                : (Schema::hasColumn('student_score', 'last_updated_at')
                    ? 'last_updated_at'
                    : (Schema::hasColumn('student_score', 'updated_at') ? 'updated_at' : null));
        }

        $lastScoreAt = null;
        if ($totalStudents > 0 && $scoreUpdatedColumn) {
            $lastScoreAt = DB::table('student_score')
                ->whereIn('enrollment_id', $enrollmentIds)
                ->max($scoreUpdatedColumn);
        }

        $lastUpdatedCandidates = array_values(array_filter([$lastAttendanceAt, $lastScoreAt]));
        $lastUpdatedAtRaw = count($lastUpdatedCandidates) > 0 ? max($lastUpdatedCandidates) : null;
        $lastUpdatedAt = $lastUpdatedAtRaw ? (string) $lastUpdatedAtRaw : null;

        $updatedBy = Auth::user()?->full_name ?? Auth::user()?->name ?? '—';
        $classStatusName = $currentClass->status?->name ?? '—';

        $dashboard = [
            'attendance' => [
                'done' => $markedMeetings,
                'total' => $totalMeetings,
                'percent' => $attendanceCompletionPercent,
            ],
            'grading' => [
                'done' => $scoredStudents,
                'total' => $totalStudents,
                'percent' => $gradingCompletionPercent,
            ],
            'last_updated_at' => $lastUpdatedAt,
            'updated_by' => $updatedBy,
            'class_status_name' => $classStatusName,
        ];

        // Attendance summary by enrollment
        $attendedCountByEnrollmentId = collect();
        if ($totalMeetings > 0) {
            $attendedStatusIds = collect();
            if (Schema::hasTable('attendance_status') && Schema::hasColumn('attendance_status', 'code')) {
                $attendedStatusIds = DB::table('attendance_status')
                    ->whereIn('code', ['PRESENT', 'LATE', 'EXCUSED'])
                    ->pluck('status_id');

                if ($attendedStatusIds->count() === 0) {
                    // Fallback: treat all non-ABSENT statuses as attended
                    $attendedStatusIds = DB::table('attendance_status')
                        ->where('code', '<>', 'ABSENT')
                        ->pluck('status_id');
                }
            }

            $attendedStatusIdList = $attendedStatusIds
                ->map(fn ($v) => (int) $v)
                ->filter(fn ($v) => $v > 0)
                ->values()
                ->all();

            if (count($attendedStatusIdList) > 0) {
                $attendedCountByEnrollmentId = Attendance::query()
                    ->whereIn('class_meeting_id', $meetingIds)
                    ->whereIn('attendance_status_id', $attendedStatusIdList)
                    ->groupBy('enrollment_id')
                    ->selectRaw('enrollment_id, COUNT(*) as attended_count')
                    ->pluck('attended_count', 'enrollment_id');
            }
        }

        // Grading structure (latest applied scheme)
        $classScheme = DB::table('class_grading_scheme')
            ->where('class_section_id', $id)
            ->orderByDesc('applied_at')
            ->orderByDesc('class_grading_scheme_id')
            ->first();

        $componentList = [];
        if ($classScheme) {
            $weightColumn = Schema::hasColumn('grading_component', 'weight_percent')
                ? 'weight_percent'
                : (Schema::hasColumn('grading_component', 'weight') ? 'weight' : null);

            $select = [
                'component_id',
                DB::raw('component_name as component_name'),
                'order_no',
            ];
            if ($weightColumn) {
                $select[] = DB::raw($weightColumn . ' as weight_percent');
            }

            $components = DB::table('grading_component')
                ->where('grading_scheme_id', $classScheme->grading_scheme_id)
                ->orderBy('order_no', 'asc')
                ->orderBy('component_id', 'asc')
                ->get($select);

            $componentList = $components->map(function ($c) {
                return [
                    'component_id' => (int) $c->component_id,
                    'component_name' => (string) $c->component_name,
                    'order_no' => $c->order_no !== null ? (int) $c->order_no : 0,
                    'weight_percent' => property_exists($c, 'weight_percent') && $c->weight_percent !== null ? (float) $c->weight_percent : 0.0,
                ];
            })->values()->all();
        }

        $componentIds = array_values(array_filter(
            array_map(fn ($c) => (int) ($c['component_id'] ?? 0), $componentList),
            fn ($v) => $v > 0
        ));

        $scoreColumn = null;
        if (Schema::hasTable('student_score')) {
            $scoreColumn = Schema::hasColumn('student_score', 'score_value')
                ? 'score_value'
                : (Schema::hasColumn('student_score', 'score') ? 'score' : null);
        }

        $scoresByEnrollment = [];
        if ($scoreColumn && count($enrollmentIds) > 0 && count($componentIds) > 0) {
            $rows = DB::table('student_score')
                ->whereIn('enrollment_id', $enrollmentIds)
                ->whereIn('component_id', $componentIds)
                ->select([
                    'enrollment_id',
                    'component_id',
                    DB::raw($scoreColumn . ' as score'),
                ])
                ->get();

            foreach ($rows as $r) {
                $enrollmentId = (int) $r->enrollment_id;
                $componentId = (int) $r->component_id;
                if ($enrollmentId <= 0 || $componentId <= 0) continue;
                if (!array_key_exists($enrollmentId, $scoresByEnrollment)) {
                    $scoresByEnrollment[$enrollmentId] = [];
                }
                $scoresByEnrollment[$enrollmentId][$componentId] = ($r->score !== null && is_numeric($r->score)) ? (float) $r->score : null;
            }
        }

        $students = array_map(function (array $student) use (
            $totalMeetings,
            $attendedCountByEnrollmentId,
            $calculationService,
            $componentList,
            $scoresByEnrollment
        ) {
            $student['student_code'] = $student['student_code'] ?? '(Thiếu mã SV)';
            $student['name'] = $student['name'] ?? '(Thiếu họ tên)';

            $enrollmentId = (int) ($student['enrollment_id'] ?? 0);
            $scoreMap = ($enrollmentId > 0) ? ($scoresByEnrollment[$enrollmentId] ?? []) : [];

            $finalResult = $calculationService->calculateFinalScore($componentList, $scoreMap);
            $finalRounded = $finalResult['rounded'] ?? null;
            $scoreStatus = $finalResult['status'] ?? ['code' => 'empty', 'label' => 'Chưa có'];

            // Attendance eligibility (used as an additional warning signal)
            $attendanceCode = 'studying';
            if ($totalMeetings > 0 && $enrollmentId > 0) {
                $attendedCount = (int) ($attendedCountByEnrollmentId[$enrollmentId] ?? 0);
                $attendancePercent = $calculationService->calculateAttendancePercent($attendedCount, (int) $totalMeetings);
                $eligibility = $calculationService->evaluateAttendanceEligibility($attendancePercent);
                $attendanceCode = (string) ($eligibility['code'] ?? 'studying');
            }

            // Final status label on Class Status page:
            // - No score => Chưa có điểm
            // - Score rules (rounded-first): Đạt / Nguy cơ / Không đạt
            // - If score is Đạt but attendance is not eligible, downgrade to warning/fail
            if ($finalRounded === null) {
                $student['status_label'] = 'Chưa có điểm';
            } else {
                $label = (string) ($scoreStatus['label'] ?? 'Chưa có');
                if ($label === 'Đạt' && $attendanceCode !== 'eligible') {
                    $label = ($attendanceCode === 'warning') ? 'Không đạt' : 'Nguy cơ';
                }
                $student['status_label'] = $label;
            }

            $student['final_score'] = $finalRounded;

            return $student;
        }, $students);

        return view('lecturer.classStatus', compact('currentClass', 'classes', 'students', 'dashboard'));
    }

    /**
     * Xuất bảng điểm (Excel/PDF)
     * GET /lecturer/class/{id}/export-scores?type=excel|pdf
     */
    public function exportScores(Request $request, $id, CalculationService $calculationService)
    {
        $lecturerId = Auth::id();

        $type = strtolower((string) $request->query('type', ''));
        if (!in_array($type, ['excel', 'pdf'], true)) {
            return response()->json([
                'message' => 'Tham số type không hợp lệ. Hỗ trợ: excel|pdf'
            ], 422);
        }

        $currentClass = ClassSection::with(['courseVersion.course', 'semester', 'status'])
            ->where('class_section_id', $id)
            ->where('lecturer_id', $lecturerId)
            ->firstOrFail();

        // Students
        $students = DB::table('enrollment as e')
            ->join('user as u', 'u.user_id', '=', 'e.student_id')
            ->where('e.class_section_id', $id)
            ->orderBy('u.code_user', 'asc')
            ->select([
                'e.enrollment_id',
                DB::raw('u.code_user as student_code'),
                DB::raw('u.full_name as full_name'),
            ])
            ->get();

        $enrollmentIds = $students->pluck('enrollment_id')->map(fn ($v) => (int) $v)->all();

        // Grading structure
        $classScheme = DB::table('class_grading_scheme')
            ->where('class_section_id', $id)
            ->orderByDesc('applied_at')
            ->orderByDesc('class_grading_scheme_id')
            ->first();

        $components = collect();
        if ($classScheme) {
            $weightColumn = Schema::hasColumn('grading_component', 'weight_percent')
                ? 'weight_percent'
                : (Schema::hasColumn('grading_component', 'weight') ? 'weight' : null);

            $select = [
                'component_id',
                DB::raw('component_name as component_name'),
                'order_no',
            ];
            if ($weightColumn) {
                $select[] = DB::raw($weightColumn . ' as weight_percent');
            }

            $components = DB::table('grading_component')
                ->where('grading_scheme_id', $classScheme->grading_scheme_id)
                ->orderBy('order_no', 'asc')
                ->orderBy('component_id', 'asc')
                ->get($select);
        }

        $componentList = $components->map(function ($c) {
            return [
                'component_id' => (int) $c->component_id,
                'component_name' => (string) $c->component_name,
                'order_no' => $c->order_no !== null ? (int) $c->order_no : 0,
                'weight_percent' => property_exists($c, 'weight_percent') && $c->weight_percent !== null ? (float) $c->weight_percent : 0.0,
            ];
        })->values()->all();

        $componentIds = array_values(array_filter(array_map(fn ($c) => (int) ($c['component_id'] ?? 0), $componentList), fn ($v) => $v > 0));

        // Scores
        $scoreColumn = Schema::hasColumn('student_score', 'score_value')
            ? 'score_value'
            : (Schema::hasColumn('student_score', 'score') ? 'score' : null);

        $scoresByEnrollment = [];
        if ($scoreColumn && count($enrollmentIds) > 0 && count($componentIds) > 0) {
            $rows = DB::table('student_score')
                ->whereIn('enrollment_id', $enrollmentIds)
                ->whereIn('component_id', $componentIds)
                ->select([
                    'enrollment_id',
                    'component_id',
                    DB::raw($scoreColumn . ' as score'),
                ])
                ->get();

            foreach ($rows as $r) {
                $enrollmentId = (int) $r->enrollment_id;
                $componentId = (int) $r->component_id;
                if ($enrollmentId <= 0 || $componentId <= 0) continue;

                if (!array_key_exists($enrollmentId, $scoresByEnrollment)) {
                    $scoresByEnrollment[$enrollmentId] = [];
                }
                $scoresByEnrollment[$enrollmentId][$componentId] = ($r->score !== null && is_numeric($r->score)) ? (float) $r->score : null;
            }
        }

        // Attendance
        $meetingKeyColumn = Schema::hasColumn('class_meeting', 'meeting_id')
            ? 'meeting_id'
            : (Schema::hasColumn('class_meeting', 'class_meeting_id') ? 'class_meeting_id' : 'id');

        $meetingIds = ClassMeeting::where('class_section_id', $id)->pluck($meetingKeyColumn);
        $totalMeetings = $meetingIds->count();

        $attendedStatusIds = collect();
        if ($totalMeetings > 0 && Schema::hasTable('attendance_status') && Schema::hasColumn('attendance_status', 'code')) {
            $attendedStatusIds = DB::table('attendance_status')
                ->whereIn('code', ['PRESENT', 'LATE', 'EXCUSED'])
                ->pluck('status_id');

            if ($attendedStatusIds->count() === 0) {
                $attendedStatusIds = DB::table('attendance_status')
                    ->where('code', '<>', 'ABSENT')
                    ->pluck('status_id');
            }
        }

        $attendedCountByEnrollmentId = collect();
        $attendedStatusIdList = $attendedStatusIds
            ->map(fn ($v) => (int) $v)
            ->filter(fn ($v) => $v > 0)
            ->values()
            ->all();

        if ($totalMeetings > 0 && count($attendedStatusIdList) > 0) {
            $attendedCountByEnrollmentId = Attendance::query()
                ->whereIn('class_meeting_id', $meetingIds)
                ->whereIn('attendance_status_id', $attendedStatusIdList)
                ->groupBy('enrollment_id')
                ->selectRaw('enrollment_id, COUNT(*) as attended_count')
                ->pluck('attended_count', 'enrollment_id');
        }

        $rows = $students->map(function ($s) use ($componentList, $scoresByEnrollment, $calculationService, $attendedCountByEnrollmentId, $totalMeetings) {
            $enrollmentId = (int) $s->enrollment_id;
            $scoreMap = $scoresByEnrollment[$enrollmentId] ?? [];
            $finalResult = $calculationService->calculateFinalScore($componentList, $scoreMap);
            $finalRounded = $finalResult['rounded'] ?? null;
            $finalStatus = $calculationService->evaluateFinalStatus($finalRounded);

            $attended = (int) ($attendedCountByEnrollmentId[$enrollmentId] ?? 0);
            $attendancePercent = $calculationService->calculateAttendancePercent($attended, (int) $totalMeetings);

            return [
                'enrollment_id' => $enrollmentId,
                'student_code' => (string) $s->student_code,
                'full_name' => (string) $s->full_name,
                'attendance_percent' => $attendancePercent,
                'final_score' => $finalRounded,
                'final_status' => $finalStatus,
                'scores' => $scoreMap,
            ];
        })->values()->all();

        if ($type === 'pdf') {
            // Fallback: render HTML that can be printed to PDF.
            // If a PDF library (e.g. dompdf) is added later, this endpoint can switch to real PDF download.
            return view('exports.class_scores_pdf', [
                'class' => $currentClass,
                'components' => $componentList,
                'rows' => $rows,
                'generatedAt' => now(),
            ]);
        }

        // Excel (.xlsx) export: require package. If missing, return a clear message.
        // (Example package: maatwebsite/excel)
        return response(
            'Tính năng xuất Excel (.xlsx) đang phát triển (chưa cài package).',
            501,
            ['Content-Type' => 'text/plain; charset=UTF-8']
        );
    }
    
}