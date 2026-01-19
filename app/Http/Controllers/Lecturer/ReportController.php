<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\ClassMeeting;
use App\Models\ClassSection;
use App\Models\Enrollment;
use App\Services\Lecturer\CalculationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReportController extends Controller
{
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
            $calculationService = app(CalculationService::class);

            $currentClass = ClassSection::where('class_section_id', $id)
                ->where('lecturer_id', $lecturerId)
                ->first();

            $classScheme = DB::table('class_grading_scheme')
                ->where('class_section_id', $id)
                ->orderByDesc('applied_at')
                ->orderByDesc('class_grading_scheme_id')
                ->first();

            if (!$classScheme) {
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
                ]);
            }

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

            $componentIds = array_values(array_filter(array_map(fn ($c) => (int) ($c['component_id'] ?? 0), $componentList), fn ($v) => $v > 0));

            $students = DB::table('enrollment as e')
                ->join('user as u', 'u.user_id', '=', 'e.student_id')
                ->where('e.class_section_id', $id)
                ->orderBy('u.code_user', 'asc')
                ->select([
                    'e.enrollment_id',
                    'u.user_id as student_id',
                    DB::raw('u.code_user as student_code'),
                    DB::raw('u.full_name as full_name'),
                ])
                ->get();

            $enrollmentIds = $students->pluck('enrollment_id')->map(fn ($v) => (int) $v)->all();

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

            $distribution = [
                '9_10' => 0,
                '8_8_9' => 0,
                '7_7_9' => 0,
                '6_6_9' => 0,
                '5_5_9' => 0,
                'below_5' => 0,
            ];
            $passFail = ['pass' => 0, 'fail' => 0];
            $warnings = [];

            foreach ($students as $s) {
                $enrollmentId = (int) $s->enrollment_id;
                $scoreMap = $scoresByEnrollment[$enrollmentId] ?? [];
                $finalResult = $calculationService->calculateFinalScore($componentList, $scoreMap);
                $finalRounded = $finalResult['rounded'] ?? null;
                if ($finalRounded === null) {
                    continue;
                }

                $band = $calculationService->scoreBand($finalRounded);
                if ($band !== null && array_key_exists($band, $distribution)) {
                    $distribution[$band] += 1;
                }

                $pf = $calculationService->passFail($finalRounded);
                if ($pf !== null && array_key_exists($pf, $passFail)) {
                    $passFail[$pf] += 1;
                }

                $warnLabel = $calculationService->warningLabel($finalRounded);
                if ($warnLabel !== null) {
                    $warnings[] = [
                        'student_id' => (int) $s->student_id,
                        'student_code' => (string) $s->student_code,
                        'full_name' => (string) $s->full_name,
                        'class_code' => (string) ($currentClass?->class_code ?? ''),
                        'total_score' => (float) $finalRounded,
                        'status' => $warnLabel,
                    ];
                }
            }

            $totalStudents = array_sum($distribution);

            return response()->json([
                'total_students' => (int) $totalStudents,
                'score_distribution' => $distribution,
                'pass_fail_ratio' => $passFail,
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

    /**
     * API: Chi tiết sinh viên trong lớp học phần (dùng cho modal Report)
     * GET /lecturer/class/{classId}/student/{studentId}/detail
     */
    public function getStudentDetail($classId, $studentId)
    {
        $lecturerId = Auth::id();

        // Ensure lecturer owns the class
        ClassSection::where('class_section_id', $classId)
            ->where('lecturer_id', $lecturerId)
            ->firstOrFail();

        // Ensure student belongs to class
        $enrollment = Enrollment::where('class_section_id', $classId)
            ->where('student_id', $studentId)
            ->whereIn('enrollment_status_id', [1, 2])
            ->with(['student'])
            ->firstOrFail();

        $student = $enrollment->student;
        $calculationService = app(CalculationService::class);

        // ===== Scores (by grading scheme) =====
        $classScheme = DB::table('class_grading_scheme')
            ->where('class_section_id', $classId)
            ->orderByDesc('applied_at')
            ->orderByDesc('class_grading_scheme_id')
            ->first();

        $componentList = [];
        $scoreMap = [];

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

            $componentIds = array_values(array_filter(array_map(fn ($c) => (int) ($c['component_id'] ?? 0), $componentList), fn ($v) => $v > 0));

            $scoreColumn = Schema::hasColumn('student_score', 'score_value')
                ? 'score_value'
                : (Schema::hasColumn('student_score', 'score') ? 'score' : null);

            if ($scoreColumn && count($componentIds) > 0) {
                $rows = DB::table('student_score')
                    ->where('enrollment_id', (int) $enrollment->enrollment_id)
                    ->whereIn('component_id', $componentIds)
                    ->select([
                        'component_id',
                        DB::raw($scoreColumn . ' as score'),
                    ])
                    ->get();

                foreach ($rows as $r) {
                    $componentId = (int) $r->component_id;
                    if ($componentId <= 0) continue;
                    $scoreMap[$componentId] = ($r->score !== null && is_numeric($r->score)) ? (float) $r->score : null;
                }
            }
        }

        $finalResult = $calculationService->calculateFinalScore($componentList, $scoreMap);
        $finalRounded = $finalResult['rounded'] ?? null;
        $finalStatus = $finalResult['status'] ?? $calculationService->evaluateScore($finalRounded);

        $componentsForJson = array_map(function ($c) use ($calculationService, $scoreMap) {
            $componentId = (int) ($c['component_id'] ?? 0);
            $raw = array_key_exists($componentId, $scoreMap) ? $scoreMap[$componentId] : null;
            $rounded = $calculationService->roundScore($raw);
            return [
                'component_id' => $componentId,
                'component_name' => (string) ($c['component_name'] ?? ''),
                'order_no' => (int) ($c['order_no'] ?? 0),
                'weight_percent' => (float) ($c['weight_percent'] ?? 0.0),
                'score' => $raw,
                'score_rounded' => $rounded,
            ];
        }, $componentList);

        // ===== Attendance stats =====
        $meetingIds = ClassMeeting::where('class_section_id', $classId)
            ->pluck('class_meeting_id')
            ->map(fn ($v) => (int) $v)
            ->values()
            ->all();

        $totalMeetings = count($meetingIds);

        $present = 0;
        $absent = 0;
        $late = 0;
        $excused = 0;
        $unmarked = 0;

        if ($totalMeetings > 0) {
            $statusIdColumn = Schema::hasColumn('attendance', 'attendance_status_id')
                ? 'attendance_status_id'
                : (Schema::hasColumn('attendance', 'status_id') ? 'status_id' : null);

            if ($statusIdColumn) {
                $counts = Attendance::where('enrollment_id', (int) $enrollment->enrollment_id)
                    ->whereIn('class_meeting_id', $meetingIds)
                    ->select([
                        DB::raw($statusIdColumn . ' as status_id'),
                        DB::raw('COUNT(*) as c'),
                    ])
                    ->groupBy(DB::raw($statusIdColumn))
                    ->get();

                $statusNameCol = Schema::hasColumn('attendance_status', 'status_name')
                    ? 'status_name'
                    : (Schema::hasColumn('attendance_status', 'name') ? 'name' : null);
                $statusCodeCol = Schema::hasColumn('attendance_status', 'status_code')
                    ? 'status_code'
                    : (Schema::hasColumn('attendance_status', 'code') ? 'code' : null);

                $statusMeta = [];
                if ($statusNameCol || $statusCodeCol) {
                    $selectCols = ['status_id'];
                    if ($statusNameCol) $selectCols[] = DB::raw($statusNameCol . ' as status_name');
                    if ($statusCodeCol) $selectCols[] = DB::raw($statusCodeCol . ' as status_code');
                    $statusMeta = DB::table('attendance_status')->get($selectCols)->keyBy('status_id')->all();
                }

                $sumMarked = 0;

                foreach ($counts as $row) {
                    $sid = (int) $row->status_id;
                    $c = (int) $row->c;
                    $sumMarked += $c;

                    $meta = $statusMeta[$sid] ?? null;
                    $code = $meta?->status_code ?? '';
                    $name = $meta?->status_name ?? '';
                    $hay = mb_strtolower(trim(($code ? $code . ' ' : '') . ($name ?? '')));

                    if ($hay === '') {
                        continue;
                    }

                    if (str_contains($hay, 'absent') || str_contains($hay, 'vang') || str_contains($hay, 'vắng')) {
                        $absent += $c;
                    } elseif (str_contains($hay, 'late') || str_contains($hay, 'muon') || str_contains($hay, 'muộn')) {
                        $late += $c;
                    } elseif (str_contains($hay, 'excuse') || str_contains($hay, 'phep') || str_contains($hay, 'phép')) {
                        $excused += $c;
                    } elseif (str_contains($hay, 'present') || str_contains($hay, 'co mat') || str_contains($hay, 'có mặt') || str_contains($hay, 'attend')) {
                        $present += $c;
                    } else {
                        // Unknown status → treat as present-like if it's not absent
                        $present += $c;
                    }
                }

                $unmarked = max(0, $totalMeetings - $sumMarked);
            }
        }

        $presentLike = $present + $late + $excused;
        $attendanceRate = $totalMeetings > 0 ? ($presentLike / $totalMeetings) * 100.0 : null;

        // ===== Warnings =====
        $warnings = [];

        if ($finalRounded !== null) {
            $warnLabel = $calculationService->warningLabel($finalRounded);
            if ($warnLabel !== null) {
                $warnings[] = [
                    'code' => 'LOW_SCORE',
                    'title' => 'Điểm tổng kết thấp',
                    'message' => 'Sinh viên đang nằm trong nhóm cảnh báo theo điểm tổng kết (đã làm tròn).',
                    'level' => 'warning',
                ];
            }

            $pf = $calculationService->passFail($finalRounded);
            if ($pf === 'fail') {
                $warnings[] = [
                    'code' => 'FAILED',
                    'title' => 'Nguy cơ rớt môn',
                    'message' => 'Điểm tổng kết hiện tại chưa đạt ngưỡng qua môn.',
                    'level' => 'danger',
                ];
            }
        }

        if ($attendanceRate !== null && $attendanceRate < 70.0) {
            $warnings[] = [
                'code' => 'LOW_ATTENDANCE',
                'title' => 'Chuyên cần kém',
                'message' => 'Tỷ lệ chuyên cần hiện tại thấp (dựa trên các buổi đã tạo).',
                'level' => 'warning',
            ];
        }

        return response()->json([
            'success' => true,
            'student' => [
                'student_id' => (int) $enrollment->student_id,
                'student_code' => (string) ($student?->code_user ?? ''),
                'full_name' => (string) ($student?->full_name ?? ''),
                'email' => (string) ($student?->email ?? ''),
                'major' => (string) ($student?->major ?? ''),
            ],
            'scores' => [
                'components' => $componentsForJson,
                'final' => [
                    'raw' => $finalResult['raw'] ?? null,
                    'rounded' => $finalRounded,
                    'status' => $finalStatus,
                ],
            ],
            'attendance' => [
                'total_meetings' => (int) $totalMeetings,
                'present' => (int) $present,
                'absent' => (int) $absent,
                'late' => (int) $late,
                'excused' => (int) $excused,
                'unmarked' => (int) $unmarked,
                'attendance_rate_percent' => $attendanceRate,
            ],
            'warnings' => $warnings,
        ]);
    }

    /**
     * Xuất báo cáo (tạm thời trả về view)
     */
    public function exportReport($id)
    {
        $lecturerId = Auth::id();

        ClassSection::where('class_section_id', $id)
            ->where('lecturer_id', $lecturerId)
            ->firstOrFail();

        return view('lecturer.reportExport', [
            'class_section_id' => (int) $id,
        ]);
    }
}
