<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\Lecturer\CalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GradingController extends Controller
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

        // Backend-computed totals/status (single source of truth for exports/reports; FE can use or ignore)
        $calculationService = app(CalculationService::class);
        $scoresByEnrollment = [];
        foreach ($scores as $r) {
            $enrollmentId = (int) ($r['enrollment_id'] ?? 0);
            $componentId = (int) ($r['component_id'] ?? 0);
            if ($enrollmentId <= 0 || $componentId <= 0) continue;
            if (!array_key_exists($enrollmentId, $scoresByEnrollment)) {
                $scoresByEnrollment[$enrollmentId] = [];
            }
            $scoresByEnrollment[$enrollmentId][$componentId] = array_key_exists('score', $r) ? $r['score'] : null;
        }

        $finalScores = [];
        foreach ($enrollmentIds as $enrollmentId) {
            $scoreMap = $scoresByEnrollment[$enrollmentId] ?? [];
            $finalResult = $calculationService->calculateFinalScore($structure, $scoreMap);
            $finalRounded = $finalResult['rounded'] ?? null;
            $status = $calculationService->evaluateFinalStatus($finalRounded);
            $finalScores[] = [
                'enrollment_id' => (int) $enrollmentId,
                'final_score' => $finalRounded,
                'status_code' => (string) ($status['code'] ?? 'empty'),
                'status_label' => (string) ($status['label'] ?? 'Chưa có'),
            ];
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
            'final_scores' => $finalScores,
            'isLocked' => false,
        ]);
    }

    /**
     * API: Lưu cấu trúc điểm và/hoặc bảng điểm
     */
    public function saveGrading(Request $request, $id)
    {
        $lecturerId = Auth::id();

        $calculationService = app(CalculationService::class);

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
            $score = $calculationService->roundScore($score);
            if ($score === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vui lòng nhập điểm hợp lệ trước khi lưu'
                ], 422);
            }
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
                $newScore = $newScore !== null ? $calculationService->roundScore($newScore) : null;

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
     * Khóa điểm (stub)
     */
    public function lockGrades($id)
    {
        $lecturerId = Auth::id();

        ClassSection::where('class_section_id', $id)
            ->where('lecturer_id', $lecturerId)
            ->firstOrFail();

        return response()->json([
            'success' => false,
            'message' => 'Chức năng khóa điểm chưa được triển khai (DB chưa có cơ chế lock).',
        ], 501);
    }
}
