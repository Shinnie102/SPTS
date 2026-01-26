<?php

namespace App\Services\Lecturer;

use App\Models\ClassSection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LecturerDashboardDataService
{
    public function __construct(
        private readonly CalculationService $calculationService
    ) {
    }

    public function getDashboardData(int $lecturerId, int $warningsLimit = 20): array
    {
        $distribution = [
            '9_10' => 0,
            '8_8_9' => 0,
            '7_7_9' => 0,
            '6_6_9' => 0,
            '5_5_9' => 0,
            'below_5' => 0,
        ];

        $warnings = [];
        $warningsCount = 0;

        $scoreColumn = Schema::hasColumn('student_score', 'score_value')
            ? 'score_value'
            : (Schema::hasColumn('student_score', 'score') ? 'score' : null);

        $classes = ClassSection::query()
            ->where('lecturer_id', $lecturerId)
            ->select(['class_section_id', 'class_code'])
            ->orderBy('class_section_id', 'desc')
            ->get();

        foreach ($classes as $class) {
            $classSectionId = (int) $class->class_section_id;
            if ($classSectionId <= 0) {
                continue;
            }

            $classScheme = DB::table('class_grading_scheme')
                ->where('class_section_id', $classSectionId)
                ->orderByDesc('applied_at')
                ->orderByDesc('class_grading_scheme_id')
                ->first();

            if (!$classScheme) {
                continue;
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

            $componentIds = array_values(array_filter(
                array_map(fn ($c) => (int) ($c['component_id'] ?? 0), $componentList),
                fn ($v) => $v > 0
            ));

            if (count($componentIds) === 0) {
                continue;
            }

            $students = DB::table('enrollment as e')
                ->join('user as u', 'u.user_id', '=', 'e.student_id')
                ->where('e.class_section_id', $classSectionId)
                ->orderBy('u.code_user', 'asc')
                ->select([
                    'e.enrollment_id',
                    'u.user_id as student_id',
                    DB::raw('u.code_user as student_code'),
                    DB::raw('u.full_name as full_name'),
                ])
                ->get();

            $enrollmentIds = $students->pluck('enrollment_id')->map(fn ($v) => (int) $v)->all();
            if (count($enrollmentIds) === 0) {
                continue;
            }

            $scoresByEnrollment = [];
            if ($scoreColumn) {
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
                    if ($enrollmentId <= 0 || $componentId <= 0) {
                        continue;
                    }
                    if (!array_key_exists($enrollmentId, $scoresByEnrollment)) {
                        $scoresByEnrollment[$enrollmentId] = [];
                    }
                    $scoresByEnrollment[$enrollmentId][$componentId] = ($r->score !== null && is_numeric($r->score)) ? (float) $r->score : null;
                }
            }

            foreach ($students as $s) {
                $enrollmentId = (int) $s->enrollment_id;
                $scoreMap = $scoresByEnrollment[$enrollmentId] ?? [];

                $finalResult = $this->calculationService->calculateFinalScore($componentList, $scoreMap);
                $finalRounded = $finalResult['rounded'] ?? null;
                if ($finalRounded === null) {
                    continue;
                }

                $band = $this->calculationService->scoreBand($finalRounded);
                if ($band !== null && array_key_exists($band, $distribution)) {
                    $distribution[$band] += 1;
                }

                $warnLabel = $this->calculationService->warningLabel($finalRounded);
                if ($warnLabel === null) {
                    continue;
                }

                $warningsCount += 1;

                if (count($warnings) < max(1, $warningsLimit)) {
                    $warnings[] = [
                        'student' => (string) $s->full_name,
                        'id' => (string) $s->student_code,
                        'classCode' => (string) ($class->class_code ?? ''),
                        'total_score' => (float) $finalRounded,
                        'reason' => 'Điểm tổng kết ' . number_format((float) $finalRounded, 1) . ' (' . $warnLabel . ')',
                    ];
                }
            }
        }

        usort($warnings, function (array $a, array $b) {
            return ($a['total_score'] <=> $b['total_score']);
        });

        $totalStudents = array_sum($distribution);

        return [
            'total_students' => (int) $totalStudents,
            'score_distribution' => $distribution,
            'academic_warnings' => array_slice($warnings, 0, max(1, $warningsLimit)),
            'warnings_count' => (int) $warningsCount,
        ];
    }
}
