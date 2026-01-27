<?php

namespace App\Services\Lecturer;

use App\Models\Attendance;
use App\Models\ClassMeeting;
use App\Models\ClassSection;
use App\Models\ClassSectionStatus;
use App\Models\Enrollment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;

class ClassStatusService
{
    public function __construct(
        private readonly CalculationService $calculationService
    ) {
    }

    /**
     * Build view-model for Lecturer Class Status page.
     *
     * NOTE: This service intentionally preserves the existing output shape
     * for the Blade view and frontend expectations.
     */
    public function getViewData(int $classSectionId, int $lecturerId): array
    {
        $currentClass = ClassSection::with(['courseVersion.course'])
            ->where('class_section_id', $classSectionId)
            ->where('lecturer_id', $lecturerId)
            ->firstOrFail();

        $currentClass->loadMissing('status');

        $statusCode = strtoupper((string) ($currentClass->status?->code ?? ''));
        $isClassLocked = in_array($statusCode, ['COMPLETED', 'CANCELLED'], true);

        $classes = ClassSection::with(['courseVersion.course'])
            ->where('lecturer_id', $lecturerId)
            ->orderBy('class_section_id', 'desc')
            ->get();

        $students = $this->getStudentsDatasetForClass($classSectionId);

        $meetingKeyColumn = Schema::hasColumn('class_meeting', 'meeting_id')
            ? 'meeting_id'
            : (Schema::hasColumn('class_meeting', 'class_meeting_id') ? 'class_meeting_id' : 'id');

        $meetingIds = ClassMeeting::where('class_section_id', $classSectionId)->pluck($meetingKeyColumn);
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
            'updated_by' => '—',
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
            ->where('class_section_id', $classSectionId)
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
            $componentList,
            $scoresByEnrollment
        ) {
            $student['student_code'] = $student['student_code'] ?? '(Thiếu mã SV)';
            $student['name'] = $student['name'] ?? '(Thiếu họ tên)';

            $enrollmentId = (int) ($student['enrollment_id'] ?? 0);
            $scoreMap = ($enrollmentId > 0) ? ($scoresByEnrollment[$enrollmentId] ?? []) : [];

            $finalResult = $this->calculationService->calculateFinalScore($componentList, $scoreMap);
            $finalRounded = $finalResult['rounded'] ?? null;
            $scoreStatus = $finalResult['status'] ?? ['code' => 'empty', 'label' => 'Chưa có'];

            // Attendance eligibility (used as an additional warning signal)
            $attendanceCode = 'studying';
            if ($totalMeetings > 0 && $enrollmentId > 0) {
                $attendedCount = (int) ($attendedCountByEnrollmentId[$enrollmentId] ?? 0);
                $attendancePercent = $this->calculationService->calculateAttendancePercent($attendedCount, (int) $totalMeetings);
                $eligibility = $this->calculationService->evaluateAttendanceEligibility($attendancePercent);
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

        return compact('currentClass', 'classes', 'students', 'dashboard', 'isClassLocked');
    }

    /**
     * Lock class data by moving class_section_status_id to COMPLETED.
     * Returns tuple: [statusCode, payload]
     */
    public function lockClass(int $classSectionId, int $lecturerId): array
    {
        $class = ClassSection::where('class_section_id', $classSectionId)
            ->where('lecturer_id', $lecturerId)
            ->firstOrFail();

        $class->loadMissing('status');
        $statusCode = strtoupper((string) ($class->status?->code ?? ''));
        if (in_array($statusCode, ['COMPLETED', 'CANCELLED'], true)) {
            return [200, [
                'success' => true,
                'message' => 'Lớp đã ở trạng thái Đã hoàn thành hoặc Đã hủy.',
            ]];
        }

        $completedStatusId = ClassSectionStatus::query()
            ->where('code', 'COMPLETED')
            ->value('status_id');

        if (!$completedStatusId) {
            return [500, [
                'success' => false,
                'message' => 'Không tìm thấy trạng thái COMPLETED trong class_section_status.',
            ]];
        }

        $class->class_section_status_id = (int) $completedStatusId;
        $class->updated_at = now();
        $class->save();

        return [200, [
            'success' => true,
            'message' => 'Khóa dữ liệu lớp học thành công. Lớp đã chuyển sang trạng thái Đã hoàn thành.',
        ]];
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
}
