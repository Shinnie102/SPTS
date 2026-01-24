<?php

namespace App\Services\Lecturer;

use App\Models\Attendance;
use App\Models\ClassMeeting;
use App\Models\ClassSection;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ClassExportService
{
    public function __construct(
        private readonly CalculationService $calculationService
    ) {
    }

    private function normalizeStatusText(mixed $status): string
    {
        if (is_array($status)) {
            $label = $status['label'] ?? $status['name'] ?? null;
            if (is_string($label) || is_numeric($label)) {
                return (string) $label;
            }

            return json_encode($status, JSON_UNESCAPED_UNICODE) ?: '';
        }

        if (is_object($status)) {
            $label = $status->label ?? $status->name ?? null;
            if (is_string($label) || is_numeric($label)) {
                return (string) $label;
            }

            return json_encode($status, JSON_UNESCAPED_UNICODE) ?: '';
        }

        if ($status === null) {
            return '';
        }

        return (string) $status;
    }

    public function exportScores(Request $request, int $classSectionId, int $lecturerId)
    {
        $type = strtolower((string) $request->query('type', ''));
        if (!in_array($type, ['excel', 'pdf'], true)) {
            return response()->json([
                'message' => 'Tham số type không hợp lệ. Hỗ trợ: excel|pdf',
            ], 422);
        }

        $currentClass = ClassSection::with(['courseVersion.course', 'semester', 'status'])
            ->where('class_section_id', $classSectionId)
            ->where('lecturer_id', $lecturerId)
            ->firstOrFail();

        // Students
        $students = DB::table('enrollment as e')
            ->join('user as u', 'u.user_id', '=', 'e.student_id')
            ->where('e.class_section_id', $classSectionId)
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
            ->where('class_section_id', $classSectionId)
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

        $componentIds = array_values(array_filter(
            array_map(fn ($c) => (int) ($c['component_id'] ?? 0), $componentList),
            fn ($v) => $v > 0
        ));

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

        $meetingIds = ClassMeeting::where('class_section_id', $classSectionId)->pluck($meetingKeyColumn);
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

        $rows = $students->map(function ($s) use ($componentList, $scoresByEnrollment, $attendedCountByEnrollmentId, $totalMeetings) {
            $enrollmentId = (int) $s->enrollment_id;
            $scoreMap = $scoresByEnrollment[$enrollmentId] ?? [];
            $finalResult = $this->calculationService->calculateFinalScore($componentList, $scoreMap);
            $finalRounded = $finalResult['rounded'] ?? null;
            $finalStatus = $this->calculationService->evaluateFinalStatus($finalRounded);
            $finalStatusText = $this->normalizeStatusText($finalStatus);

            $attended = (int) ($attendedCountByEnrollmentId[$enrollmentId] ?? 0);
            $attendancePercent = $this->calculationService->calculateAttendancePercent($attended, (int) $totalMeetings);

            return [
                'enrollment_id' => $enrollmentId,
                'student_code' => (string) $s->student_code,
                'full_name' => (string) $s->full_name,
                'attendance_percent' => $attendancePercent,
                'final_score' => $finalRounded,
                'final_status' => $finalStatus,
                'final_status_text' => $finalStatusText,
                'scores' => $scoreMap,
            ];
        })->values()->all();

        $courseName = (string) data_get($currentClass, 'courseVersion.course.course_name', data_get($currentClass, 'courseVersion.course.name', ''));
        $classCode = (string) data_get($currentClass, 'class_code', data_get($currentClass, 'code', ''));
        $safeCourse = trim($courseName) !== '' ? $courseName : ('Lop_' . $classSectionId);
        $safeCourse = preg_replace('/[^\p{L}\p{N}\-_ ]/u', '', $safeCourse) ?: ('Lop_' . $classSectionId);
        $dateStamp = now()->format('Ymd_His');

        if ($type === 'pdf') {
            $html = view('exports.class_scores_pdf', [
                'class' => $currentClass,
                'components' => $componentList,
                'rows' => $rows,
                'generatedAt' => now(),
            ])->render();

            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            $filename = sprintf('BangDiem_%s_%s_%s.pdf', $classCode !== '' ? $classCode : $classSectionId, $safeCourse, $dateStamp);

            return response($dompdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        }

        $headers = [
            'STT',
            'Mã SV',
            'Họ tên',
        ];
        foreach ($componentList as $c) {
            $w = isset($c['weight_percent']) ? (float) $c['weight_percent'] : 0.0;
            $label = (string) ($c['component_name'] ?? '');
            $headers[] = trim($label) !== '' ? sprintf('%s (%.0f%%)', $label, $w) : sprintf('TP %d (%.0f%%)', (int) ($c['order_no'] ?? 0), $w);
        }
        $headers[] = 'Chuyên cần (%)';
        $headers[] = 'Điểm tổng kết';
        $headers[] = 'Kết quả';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Bảng điểm');

        $sheet->fromArray($headers, null, 'A1');

        $excelRows = [];
        foreach ($rows as $i => $r) {
            $line = [
                $i + 1,
                (string) ($r['student_code'] ?? ''),
                (string) ($r['full_name'] ?? ''),
            ];

            $scoreMap = is_array($r['scores'] ?? null) ? $r['scores'] : [];
            foreach ($componentList as $c) {
                $componentId = (int) ($c['component_id'] ?? 0);
                $v = $componentId > 0 ? ($scoreMap[$componentId] ?? null) : null;
                $line[] = ($v === null) ? '' : (float) $v;
            }

            $line[] = (float) ($r['attendance_percent'] ?? 0);
            $line[] = ($r['final_score'] === null) ? '' : (float) $r['final_score'];
            $line[] = (string) ($r['final_status_text'] ?? '');

            $excelRows[] = $line;
        }

        if (count($excelRows) > 0) {
            $sheet->fromArray($excelRows, null, 'A2');
        }

        // Basic formatting
        $lastCol = Coordinate::stringFromColumnIndex(count($headers));
        $lastRow = 1 + max(1, count($excelRows));
        $sheet->getStyle('A1:' . $lastCol . '1')->getFont()->setBold(true);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:' . $lastCol . '1');
        $sheet->getStyle('A1:' . $lastCol . $lastRow)
            ->getAlignment()
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        for ($col = 1; $col <= count($headers); $col++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setAutoSize(true);
        }

        $filename = sprintf('BangDiem_%s_%s_%s.xlsx', $classCode !== '' ? $classCode : $classSectionId, $safeCourse, $dateStamp);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
