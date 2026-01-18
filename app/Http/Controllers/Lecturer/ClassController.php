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
     * Hiện tại điều hướng về tab Attendance để tránh lỗi route thiếu view riêng.
     */
    public function show($id)
    {
        $lecturerId = Auth::id();

        // Lưu lớp đã chọn vào session
        session(['selected_class_id' => $id]);

        // Ensure lecturer owns the class
        ClassSection::where('class_section_id', $id)
            ->where('lecturer_id', $lecturerId)
            ->firstOrFail();

        return redirect()->route('lecturer.attendance', ['id' => $id]);
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
        
        // Lấy danh sách buổi học của lớp
        $meetings = ClassMeeting::where('class_section_id', $id)
            ->orderBy('meeting_date', 'asc')
            ->get();
        
        // Lấy enrollment của sinh viên trong lớp
        $enrollments = Enrollment::where('class_section_id', $id)
            ->whereIn('enrollment_status_id', [1, 2]) // ACTIVE và COMPLETED
            ->with(['student'])
            ->get();
        
        // Lấy danh sách trạng thái điểm danh
        $attendanceStatuses = AttendanceStatus::all()->keyBy('status_id');
        
        // Nếu có buổi học, lấy dữ liệu điểm danh cho buổi đầu tiên
        $currentMeeting = $meetings->first();
        $attendanceData = [];

        $attendanceRecords = collect();
        if ($currentMeeting) {
            $attendanceRecords = Attendance::where('class_meeting_id', $currentMeeting->class_meeting_id)
                ->get()
                ->keyBy('enrollment_id');
        }

        // Luôn chuẩn bị danh sách sinh viên (kể cả khi chưa có buổi)
        foreach ($enrollments as $enrollment) {
            $attendance = $attendanceRecords->get($enrollment->enrollment_id);
            $attendanceData[] = [
                'enrollment_id' => $enrollment->enrollment_id,
                'student_id' => $enrollment->student_id,
                'student_code' => $enrollment->student->code_user,
                'name' => $enrollment->student->full_name,
                'attendance_status_id' => $attendance ? $attendance->attendance_status_id : null,
                'status_name' => $attendance ?
                    ($attendanceStatuses[$attendance->attendance_status_id]->status_name ?? null) : null,
            ];
        }
        
        // Kiểm tra xem buổi học đã có điểm danh chưa
        $isAttendanceLocked = false;
        if ($currentMeeting) {
            $attendanceCount = Attendance::where('class_meeting_id', $currentMeeting->class_meeting_id)->count();
            $isAttendanceLocked = $attendanceCount > 0;
        }
        
        return view('lecturer.attendance', compact(
            'currentClass', 
            'classes', 
            'meetings',
            'currentMeeting',
            'attendanceData',
            'isAttendanceLocked'
        ));
    }
    
    /**
     * Lấy dữ liệu điểm danh cho buổi học
     */
    public function getAttendanceData(Request $request, $classId, $meetingId)
    {
        $lecturerId = Auth::id();
        
        // Kiểm tra quyền truy cập
        $class = ClassSection::where('class_section_id', $classId)
            ->where('lecturer_id', $lecturerId)
            ->firstOrFail();
        
        $meeting = ClassMeeting::where('class_meeting_id', $meetingId)
            ->where('class_section_id', $classId)
            ->firstOrFail();
        
        // Lấy enrollment của sinh viên trong lớp
        $enrollments = Enrollment::where('class_section_id', $classId)
            ->whereIn('enrollment_status_id', [1, 2])
            ->with(['student'])
            ->get();
        
        // Lấy dữ liệu điểm danh hiện có
        $attendanceRecords = Attendance::where('class_meeting_id', $meetingId)
            ->get()
            ->keyBy('enrollment_id');
        
        // Lấy danh sách trạng thái điểm danh
        $attendanceStatuses = AttendanceStatus::all()->keyBy('status_id');
        
        $students = [];
        foreach ($enrollments as $enrollment) {
            $attendance = $attendanceRecords->get($enrollment->enrollment_id);
            $students[] = [
                'enrollment_id' => $enrollment->enrollment_id,
                'student_id' => $enrollment->student_id,
                'student_code' => $enrollment->student->code_user,
                'name' => $enrollment->student->full_name,
                'attendance_status_id' => $attendance ? $attendance->attendance_status_id : null,
                'status_name' => $attendance ? 
                    ($attendanceStatuses[$attendance->attendance_status_id]->status_name ?? null) : null,
            ];
        }
        
        // Kiểm tra xem buổi học đã có điểm danh chưa
        $isLocked = Attendance::where('class_meeting_id', $meetingId)->count() > 0;
        
        return response()->json([
            'success' => true,
            'students' => $students,
            'meeting' => $meeting,
            'isLocked' => $isLocked
        ]);
    }
    
    /**
     * Lưu điểm danh
     */
    public function saveAttendance(Request $request, $classId)
    {
        $lecturerId = Auth::id();
        
        // Kiểm tra quyền truy cập
        $class = ClassSection::where('class_section_id', $classId)
            ->where('lecturer_id', $lecturerId)
            ->firstOrFail();
        
        // Không cho lưu vào buổi đã tồn tại trong nghiệp vụ hiện tại
        if ($request->filled('meeting_id')) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể lưu vào buổi đã tồn tại. Vui lòng tạo buổi mới và chọn ngày trước khi lưu.'
            ], 422);
        }

        $validated = $request->validate([
            'class_section_id' => 'nullable|integer',
            'meeting_date' => 'required|date_format:Y-m-d',
            'attendance' => 'required|array|min:1',
            'attendance.*.enrollment_id' => 'required|exists:enrollment,enrollment_id',
            'attendance.*.status' => 'required|exists:attendance_status,status_id',
        ]);

        if (isset($validated['class_section_id']) && (int) $validated['class_section_id'] !== (int) $classId) {
            return response()->json([
                'success' => false,
                'message' => 'class_section_id không khớp với lớp đang thao tác.'
            ], 422);
        }

        // Đảm bảo các enrollment thuộc đúng lớp
        $enrollmentIds = collect($validated['attendance'])->pluck('enrollment_id')->unique()->values();
        $validCount = Enrollment::where('class_section_id', $classId)
            ->whereIn('enrollment_id', $enrollmentIds)
            ->count();

        if ($validCount !== $enrollmentIds->count()) {
            return response()->json([
                'success' => false,
                'message' => 'Danh sách sinh viên không hợp lệ cho lớp này.'
            ], 422);
        }

        if (!Schema::hasTable('meeting_status')) {
            return response()->json([
                'success' => false,
                'message' => 'Thiếu bảng meeting_status để tạo buổi điểm danh.'
            ], 500);
        }

        try {
            DB::beginTransaction();

            $meetingStatusId = DB::table('meeting_status')
                ->whereIn('code', ['OPEN', 'ACTIVE'])
                ->orderByRaw("FIELD(code, 'OPEN', 'ACTIVE')")
                ->value('status_id');

            if (!$meetingStatusId) {
                $meetingStatusId = DB::table('meeting_status')->orderBy('status_id')->value('status_id');
            }

            if (!$meetingStatusId) {
                throw new \RuntimeException('Không tìm thấy trạng thái mặc định để tạo buổi điểm danh.');
            }

            $meeting = ClassMeeting::create([
                'class_section_id' => (int) $classId,
                'meeting_date' => $validated['meeting_date'],
                'time_slot_id' => null,
                'room_id' => null,
                'meeting_status_id' => (int) $meetingStatusId,
                'note' => null,
            ]);

            foreach ($validated['attendance'] as $row) {
                Attendance::create([
                    'enrollment_id' => $row['enrollment_id'],
                    'class_meeting_id' => $meeting->class_meeting_id,
                    'attendance_status_id' => $row['status'],
                    'marked_at' => now(),
                ]);
            }

            DB::commit();

            $meetings = ClassMeeting::where('class_section_id', $classId)
                ->orderBy('meeting_date', 'asc')
                ->orderBy('class_meeting_id', 'asc')
                ->get(['class_meeting_id', 'meeting_date'])
                ->map(function ($m) {
                    return [
                        'class_meeting_id' => (int) $m->class_meeting_id,
                        'meeting_date' => (string) $m->meeting_date,
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,
                'message' => 'Điểm danh đã được lưu thành công',
                'created_meeting_id' => (int) $meeting->class_meeting_id,
                'meetings' => $meetings,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lưu điểm danh: ' . $e->getMessage()
            ], 500);
        }
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

        $students = $this->getStudentsDatasetForClass((int) $id);

        return view('lecturer.grading', compact('currentClass', 'classes', 'students'));
    }

    /**
     * API: Lấy dữ liệu cấu trúc điểm + bảng điểm cho lớp
     */
    public function getGradingData($id)
    {
        $lecturerId = Auth::id();

        // Ensure lecturer owns the class
        ClassSection::where('class_section_id', $id)
            ->where('lecturer_id', $lecturerId)
            ->firstOrFail();

        $classScheme = DB::table('class_grading_scheme')
            ->where('class_section_id', $id)
            ->orderByDesc('applied_at')
            ->orderByDesc('class_grading_scheme_id')
            ->first();

        if (!$classScheme) {
            return response()->json([
                'message' => 'Lớp chưa được gán grading scheme.'
            ], 422);
        }

        // IMPORTANT: No lock/chốt điểm mechanism in current DB.
        // Keep response key for FE compatibility but always false.
        $isLocked = false;

        $weightColumn = Schema::hasColumn('grading_component', 'weight_percent')
            ? 'weight_percent'
            : (Schema::hasColumn('grading_component', 'weight') ? 'weight' : null);

        $minScoreColumn = Schema::hasColumn('grading_component', 'minimum_score')
            ? 'minimum_score'
            : null;

        $componentQuery = DB::table('grading_component')
            ->where('grading_scheme_id', $classScheme->grading_scheme_id)
            ->orderBy('order_no', 'asc')
            ->orderBy('component_id', 'asc');

        $componentSelect = [
            'component_id',
            DB::raw('component_name as name'),
        ];

        if ($weightColumn) {
            $componentSelect[] = DB::raw($weightColumn . ' as weight_percent');
        }

        if ($minScoreColumn) {
            $componentSelect[] = DB::raw($minScoreColumn . ' as minimum_score');
        }

        $components = $componentQuery->get($componentSelect);

        $structure = $components
            ->map(function ($c) {
                $weightPercent = property_exists($c, 'weight_percent') ? (float) $c->weight_percent : 0.0;
                $minimumScore = property_exists($c, 'minimum_score') ? (float) $c->minimum_score : 0.0;

                return [
                    'component_id' => (int) $c->component_id,
                    'name' => (string) $c->name,
                    'weight' => $weightPercent / 100,
                    'minimum_score' => $minimumScore,
                ];
            })
            ->values()
            ->all();

        // SQL source of truth: enrollment is the ROOT entity and enrollment_status must be respected.
        // Do NOT hard-code enrollment_status_id values.
        $students = DB::table('enrollment as e')
            ->join('user as u', 'u.user_id', '=', 'e.student_id')
            ->join('enrollment_status as es', 'es.status_id', '=', 'e.enrollment_status_id')
            ->where('e.class_section_id', $id)
            ->orderBy('u.code_user', 'asc')
            ->select([
                'e.enrollment_id',
                DB::raw('u.code_user as student_code'),
                DB::raw('u.full_name as full_name'),
            ])
            ->get();

        $enrollmentIds = $students->pluck('enrollment_id')->map(fn ($v) => (int) $v)->all();
        $componentIds = array_values(array_filter(
            array_unique(array_map(fn ($s) => (int) ($s['component_id'] ?? 0), $structure)),
            fn ($v) => $v > 0
        ));

        $scoreColumn = Schema::hasColumn('student_score', 'score_value')
            ? 'score_value'
            : (Schema::hasColumn('student_score', 'score') ? 'score' : null);

        // SQL source of truth: enrollment ROOT + LEFT JOIN student_score (optional)
        // Only consider components that belong to the applied grading scheme.
        $scores = [];
        if ($scoreColumn && count($enrollmentIds) > 0 && count($componentIds) > 0) {
            $rows = DB::table('enrollment as e')
                ->join('enrollment_status as es', 'es.status_id', '=', 'e.enrollment_status_id')
                ->leftJoin('student_score as ss', function ($join) use ($componentIds) {
                    $join->on('e.enrollment_id', '=', 'ss.enrollment_id');
                    // Keep LEFT JOIN semantics: constrain component_id inside join condition.
                    if (count($componentIds) > 0) {
                        $join->whereIn('ss.component_id', $componentIds);
                    }
                })
                ->leftJoin('grading_component as gc', function ($join) use ($classScheme) {
                    $join->on('gc.component_id', '=', 'ss.component_id')
                        ->where('gc.grading_scheme_id', '=', $classScheme->grading_scheme_id);
                })
                ->where('e.class_section_id', $id)
                ->select([
                    'e.enrollment_id',
                    DB::raw('gc.component_id as component_id'),
                    DB::raw('ss.' . $scoreColumn . ' as score'),
                    // Schema verified: no status table/column currently; keep key for FE compatibility
                    DB::raw('NULL as score_status_code'),
                ])
                ->orderBy('e.enrollment_id', 'asc')
                ->orderBy('gc.component_id', 'asc')
                ->get();

            $scores = $rows
                ->filter(fn ($r) => $r->component_id !== null)
                ->map(function ($r) {
                    return [
                        'enrollment_id' => (int) $r->enrollment_id,
                        'component_id' => (int) $r->component_id,
                        'score' => $r->score !== null ? (float) $r->score : null,
                        'score_status_code' => null,
                    ];
                })
                ->values()
                ->all();
        }

        return response()->json([
            'structure' => $structure,
            'students' => $students->map(function ($s) {
                return [
                    'enrollment_id' => (int) $s->enrollment_id,
                    'student_code' => (string) $s->student_code,
                    'full_name' => (string) $s->full_name,
                ];
            })->values()->all(),
            'scores' => $scores,
            'isLocked' => false,
        ]);
    }

    /**
     * API: Lưu cấu trúc điểm và/hoặc bảng điểm
     */
    public function saveGrading(Request $request, $id)
    {
        $lecturerId = Auth::id();

        // Ensure lecturer owns the class
        ClassSection::where('class_section_id', $id)
            ->where('lecturer_id', $lecturerId)
            ->firstOrFail();

        // Two modes on the same endpoint (no route changes):
        // - scores save: {scores:[{enrollment_id, component_id, score}]}
        // - structure save (TEMP): {structure:[{id, component, weight}, ...]}
        $scoresInput = $request->input('scores');
        $structureInput = $request->input('structure');

        $classScheme = DB::table('class_grading_scheme')
            ->where('class_section_id', $id)
            ->orderByDesc('applied_at')
            ->orderByDesc('class_grading_scheme_id')
            ->first();

        if (!$classScheme) {
            return response()->json([
                'success' => false,
                'message' => 'Lớp chưa được gán grading scheme.'
            ], 422);
        }

        // IMPORTANT: No lock/chốt điểm mechanism in current DB.

        // TEMP: structure saving
        if (is_array($structureInput)) {
            // Validate structure payload
            $items = $structureInput;
            if (count($items) === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cấu trúc điểm không hợp lệ'
                ], 422);
            }

            $weightColumn = Schema::hasColumn('grading_component', 'weight_percent')
                ? 'weight_percent'
                : (Schema::hasColumn('grading_component', 'weight') ? 'weight' : null);

            if (!$weightColumn) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cấu trúc điểm không hợp lệ'
                ], 422);
            }

            // Collect component weights (ignore "Tổng" row with id<=0)
            $updates = [];
            $sum = 0;
            foreach ($items as $it) {
                $cid = (int) ($it['id'] ?? 0);
                if ($cid <= 0) {
                    continue;
                }
                $w = $it['weight'] ?? null;
                if (!is_numeric($w)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cấu trúc điểm không hợp lệ'
                    ], 422);
                }
                $w = (int) $w;
                if ($w < 0 || $w > 100) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cấu trúc điểm không hợp lệ'
                    ], 422);
                }
                $updates[$cid] = $w;
                $sum += $w;
            }

            if (count($updates) === 0 || $sum !== 100) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cấu trúc điểm không hợp lệ'
                ], 422);
            }

            // Ensure all components belong to the applied grading scheme
            $validComponentIds = DB::table('grading_component')
                ->where('grading_scheme_id', $classScheme->grading_scheme_id)
                ->whereIn('component_id', array_keys($updates))
                ->pluck('component_id')
                ->map(fn ($v) => (int) $v)
                ->all();

            if (count($validComponentIds) !== count($updates)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cấu trúc điểm không hợp lệ'
                ], 422);
            }

            DB::beginTransaction();
            try {
                foreach ($updates as $componentId => $weightPercent) {
                    DB::table('grading_component')
                        ->where('grading_scheme_id', $classScheme->grading_scheme_id)
                        ->where('component_id', (int) $componentId)
                        ->update([
                            $weightColumn => (int) $weightPercent,
                        ]);
                }

                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Lưu cấu trúc điểm thành công.'
                ]);
            } catch (\Throwable $e) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi lưu cấu trúc điểm. Vui lòng thử lại.'
                ], 500);
            }
        }

        // Scores saving
        if (!is_array($scoresInput) || count($scoresInput) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng nhập điểm hợp lệ trước khi lưu'
            ], 422);
        }

        // Validate score rows: score must be numeric (no null/empty) to avoid SQL errors and bad writes
        foreach ($scoresInput as $row) {
            $score = $row['score'] ?? null;
            if (!is_numeric($score)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vui lòng nhập điểm hợp lệ trước khi lưu'
                ], 422);
            }
            $score = (float) $score;
            if ($score < 0 || $score > 10) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vui lòng nhập điểm hợp lệ trước khi lưu'
                ], 422);
            }

            $enrollmentId = (int) ($row['enrollment_id'] ?? 0);
            $componentId = (int) ($row['component_id'] ?? 0);
            if ($enrollmentId <= 0 || $componentId <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ.'
                ], 422);
            }
        }

        $scores = $scoresInput;
        $enrollmentIds = array_values(array_unique(array_map(fn ($r) => (int) $r['enrollment_id'], $scores)));
        $componentIds = array_values(array_unique(array_map(fn ($r) => (int) $r['component_id'], $scores)));

        // SQL source of truth: enrollment is ROOT; respect enrollment_status via join, but do NOT hard-code ids.
        $validEnrollmentIds = DB::table('enrollment as e')
            ->join('enrollment_status as es', 'es.status_id', '=', 'e.enrollment_status_id')
            ->where('e.class_section_id', $id)
            ->whereIn('e.enrollment_id', $enrollmentIds)
            ->pluck('e.enrollment_id')
            ->map(fn ($v) => (int) $v)
            ->all();

        $validEnrollmentLookup = array_fill_keys($validEnrollmentIds, true);

        // Validate components belong to applied grading scheme
        $validComponentIds = DB::table('grading_component')
            ->where('grading_scheme_id', $classScheme->grading_scheme_id)
            ->whereIn('component_id', $componentIds)
            ->pluck('component_id')
            ->map(fn ($v) => (int) $v)
            ->all();

        $validComponentLookup = array_fill_keys($validComponentIds, true);

        // If any enrollment/component is invalid, reject the whole save so FE shows a top warning.
        $invalidRows = 0;
        foreach ($scores as $row) {
            $enrollmentId = (int) ($row['enrollment_id'] ?? 0);
            $componentId = (int) ($row['component_id'] ?? 0);
            if ($enrollmentId <= 0 || $componentId <= 0) {
                $invalidRows++;
                continue;
            }
            if (!isset($validEnrollmentLookup[$enrollmentId]) || !isset($validComponentLookup[$componentId])) {
                $invalidRows++;
            }
        }
        if ($invalidRows > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ (sinh viên hoặc thành phần điểm không thuộc lớp/scheme).'
            ], 422);
        }

        $scoreColumn = Schema::hasColumn('student_score', 'score_value')
            ? 'score_value'
            : (Schema::hasColumn('student_score', 'score') ? 'score' : null);

        if (!$scoreColumn) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy cột điểm trong student_score.'
            ], 500);
        }

        $updatedAtColumn = Schema::hasColumn('student_score', 'last_updated_at') ? 'last_updated_at' : null;

        // Verified schema: student_score.status_id and score_status table do not exist.
        $hasScoreStatus = false;

        DB::beginTransaction();

        try {
            // Preload existing scores to avoid N+1 and handle potential duplicates safely.
            $existingRows = DB::table('student_score')
                ->whereIn('enrollment_id', $enrollmentIds)
                ->whereIn('component_id', $componentIds)
                ->select([
                    'student_score_id',
                    'enrollment_id',
                    'component_id',
                    DB::raw($scoreColumn . ' as score_value'),
                ])
                ->orderByDesc('student_score_id')
                ->get();

            $existingMap = [];
            foreach ($existingRows as $r) {
                $key = ((int) $r->enrollment_id) . ':' . ((int) $r->component_id);
                // First one wins due to orderByDesc(student_score_id)
                if (!isset($existingMap[$key])) {
                    $existingMap[$key] = $r;
                }
            }

            foreach ($scores as $row) {
                $enrollmentId = (int) $row['enrollment_id'];
                $componentId = (int) $row['component_id'];
                $newScore = array_key_exists('score', $row) ? $row['score'] : null;
                $newScore = $newScore === '' ? null : $newScore;
                $newScore = $newScore !== null ? (float) $newScore : null;

                // Already validated above; no silent skipping.

                $existing = $existingMap[$enrollmentId . ':' . $componentId] ?? null;

                $now = now();

                if (!$existing) {
                    $insert = [
                        'enrollment_id' => $enrollmentId,
                        'component_id' => $componentId,
                        $scoreColumn => $newScore,
                    ];

                    if ($updatedAtColumn) {
                        $insert[$updatedAtColumn] = $now;
                    }

                    DB::table('student_score')->insert($insert);
                    continue;
                }

                $studentScoreId = (int) ($existing->student_score_id ?? 0);
                $oldScore = $existing->score_value;
                $oldScore = $oldScore !== null ? (float) $oldScore : null;

                $scoreStatusCode = '';

                $sameValue = ($oldScore === null && $newScore === null)
                    || ($oldScore !== null && $newScore !== null && abs($oldScore - $newScore) < 0.00001);

                if ($sameValue) {
                    continue;
                }

                // No per-score CONFIRMED lock in current schema.

                $update = [
                    $scoreColumn => $newScore,
                ];

                if ($updatedAtColumn) {
                    $update[$updatedAtColumn] = $now;
                }

                DB::table('student_score')
                    ->where('student_score_id', $studentScoreId)
                    ->update($update);

                // History required on update
                if (Schema::hasTable('student_score_history')) {
                    DB::table('student_score_history')->insert([
                        'student_score_id' => $studentScoreId,
                        'old_value' => $oldScore,
                        'new_value' => $newScore,
                        'changed_by' => $lecturerId,
                        'changed_at' => $now,
                        'change_reason' => 'LECTURER_UPDATE',
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Lưu điểm thành công.'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lưu điểm. Vui lòng thử lại.',
            ], 500);
        }
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

        $attendedCountByEnrollmentId = collect();
        if ($totalMeetings > 0) {
            $attendedCountByEnrollmentId = Attendance::query()
                ->selectRaw('enrollment_id, SUM(CASE WHEN attendance_status_id IN (1, 3, 4) THEN 1 ELSE 0 END) AS attended_count')
                ->whereIn('class_meeting_id', $meetingIds)
                ->groupBy('enrollment_id')
                ->pluck('attended_count', 'enrollment_id');
        }

        $students = array_map(function (array $student) use ($totalMeetings, $attendedCountByEnrollmentId) {
            $student['student_code'] = $student['student_code'] ?? '(Thiếu mã SV)';
            $student['name'] = $student['name'] ?? '(Thiếu họ tên)';

            if ($totalMeetings === 0) {
                $student['status_label'] = 'Đang học';
                return $student;
            }

            $attendedCount = (int) ($attendedCountByEnrollmentId[$student['enrollment_id']] ?? 0);
            $attendanceRate = $attendedCount / $totalMeetings;

            if ($attendanceRate >= 0.8) {
                $student['status_label'] = 'Đủ điều kiện';
            } elseif ($attendanceRate < 0.5) {
                $student['status_label'] = 'Cảnh báo';
            } else {
                $student['status_label'] = 'Đang học';
            }

            return $student;
        }, $students);

        return view('lecturer.classStatus', compact('currentClass', 'classes', 'students', 'dashboard'));
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

        $students = $this->getStudentsDatasetForClass((int) $id);

        return view('lecturer.report', compact('currentClass', 'classes', 'students'));
    }

    /**
     * API: Dữ liệu báo cáo cho lớp học phần (Lecturer)
     * GET /lecturer/class/{class_section_id}/report-data
     */
    public function getReportData($id)
    {
        $lecturerId = Auth::id();

        // Ensure lecturer owns the class
        ClassSection::where('class_section_id', $id)
            ->where('lecturer_id', $lecturerId)
            ->firstOrFail();

        try {
            // Donut chart pass/fail threshold (AUTHORITATIVE): >= 4 is pass, < 4 is fail.
            $passThreshold = 4.0;

            // Subquery: total_score per enrollment = AVG(student_score.score_value)
            // NOTE: score_count=0 means student has no score -> excluded from warning list.
            $totalsSub = DB::table('enrollment as e')
                ->leftJoin('student_score as ss', 'ss.enrollment_id', '=', 'e.enrollment_id')
                ->where('e.class_section_id', $id)
                ->groupBy('e.enrollment_id')
                ->select([
                    'e.enrollment_id',
                    DB::raw('AVG(ss.score_value) as total_score'),
                    DB::raw('COUNT(ss.student_score_id) as score_count'),
                ]);

            $distributionRow = DB::query()
                ->fromSub($totalsSub, 't')
                ->selectRaw(
                    "SUM(CASE WHEN t.score_count > 0 AND t.total_score >= 9 AND t.total_score <= 10 THEN 1 ELSE 0 END) as `9_10`,
                     SUM(CASE WHEN t.score_count > 0 AND t.total_score >= 8 AND t.total_score < 9 THEN 1 ELSE 0 END) as `8_8_9`,
                     SUM(CASE WHEN t.score_count > 0 AND t.total_score >= 7 AND t.total_score < 8 THEN 1 ELSE 0 END) as `7_7_9`,
                     SUM(CASE WHEN t.score_count > 0 AND t.total_score >= 6 AND t.total_score < 7 THEN 1 ELSE 0 END) as `6_6_9`,
                     SUM(CASE WHEN t.score_count > 0 AND t.total_score >= 5 AND t.total_score < 6 THEN 1 ELSE 0 END) as `5_5_9`,
                     SUM(CASE WHEN t.score_count > 0 AND t.total_score < 5 THEN 1 ELSE 0 END) as `below_5`"
                )
                ->first();

            $totalStudentsRow = DB::query()
                ->fromSub($totalsSub, 't')
                ->selectRaw('SUM(CASE WHEN t.score_count > 0 THEN 1 ELSE 0 END) as total_students')
                ->first();

            $passFailRow = DB::query()
                ->fromSub($totalsSub, 't')
                ->selectRaw(
                    "SUM(CASE WHEN t.score_count > 0 AND t.total_score >= ? THEN 1 ELSE 0 END) as pass,
                     SUM(CASE WHEN t.score_count > 0 AND t.total_score <  ? THEN 1 ELSE 0 END) as fail",
                    [$passThreshold, $passThreshold]
                )
                ->first();

            // Academic warnings list: computed ON-THE-FLY from total_score only.
            // Rules (FINAL):
            // total < 4.0 => "Rớt"
            // 4.0 ≤ total < 5.0 => "Nguy cơ cao"
            // 5.0 ≤ total < 6.0 => "Cảnh báo"
            // total ≥ 6.0 => NOT SHOWN
            // total = NULL (no score) => EXCLUDE
            $warningRows = DB::table('enrollment as e')
                ->join('class_section as cs', 'cs.class_section_id', '=', 'e.class_section_id')
                ->join('user as u', 'u.user_id', '=', 'e.student_id')
                ->joinSub($totalsSub, 't', function ($join) {
                    $join->on('t.enrollment_id', '=', 'e.enrollment_id');
                })
                ->where('e.class_section_id', $id)
                ->where('t.score_count', '>', 0)
                ->where('t.total_score', '<', 6)
                ->orderBy('u.code_user', 'asc')
                ->select([
                    'u.user_id as student_id',
                    'u.code_user as student_code',
                    'u.full_name as full_name',
                    'cs.class_code as class_code',
                    't.total_score as total_score',
                ])
                ->get();

            $warnings = $warningRows
                ->map(function ($r) {
                    $total = $r->total_score !== null ? (float) $r->total_score : null;
                    if ($total === null) {
                        return null;
                    }

                    $status = null;
                    if ($total < 4.0) {
                        $status = 'Rớt';
                    } elseif ($total < 5.0) {
                        $status = 'Nguy cơ cao';
                    } elseif ($total < 6.0) {
                        $status = 'Cảnh báo';
                    }

                    if ($status === null) {
                        return null;
                    }

                    return [
                        'student_id' => (int) $r->student_id,
                        'student_code' => (string) $r->student_code,
                        'full_name' => (string) $r->full_name,
                        'class_code' => (string) $r->class_code,
                        'total_score' => $total,
                        'status' => $status,
                    ];
                })
                ->filter()
                ->values()
                ->all();

            return response()->json([
                'total_students' => (int) ($totalStudentsRow->total_students ?? 0),
                'score_distribution' => [
                    '9_10' => (int) ($distributionRow->{"9_10"} ?? 0),
                    '8_8_9' => (int) ($distributionRow->{"8_8_9"} ?? 0),
                    '7_7_9' => (int) ($distributionRow->{"7_7_9"} ?? 0),
                    '6_6_9' => (int) ($distributionRow->{"6_6_9"} ?? 0),
                    '5_5_9' => (int) ($distributionRow->{"5_5_9"} ?? 0),
                    'below_5' => (int) ($distributionRow->below_5 ?? 0),
                ],
                'pass_fail_ratio' => [
                    'pass' => (int) ($passFailRow->pass ?? 0),
                    'fail' => (int) ($passFailRow->fail ?? 0),
                ],
                'academic_warnings' => $warnings,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'total_students' => 0,
                'score_distribution' => [
                    '9_10' => 0,
                    '8_8_9' => 0,
                    '7_7_9' => 0,
                    '6_6_9' => 0,
                    '5_5_9' => 0,
                    'below_5' => 0,
                ],
                'pass_fail_ratio' => [
                    'pass' => 0,
                    'fail' => 0,
                ],
                'academic_warnings' => [],
            ], 500);
        }
    }
}