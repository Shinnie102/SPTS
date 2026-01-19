<?php

namespace App\Services\Lecturer;

class CalculationService
{
    public function roundScore(?float $score): ?float
    {
        if ($score === null || !is_finite($score)) {
            return null;
        }

        // IMPORTANT: status evaluation must use the 1-decimal rounded score.
        // Use HALF_UP so 3.96 -> 4.0 and 3.94 -> 3.9.
        return round($score, 1, PHP_ROUND_HALF_UP);
    }

    public function evaluateScore(?float $rawScore): array
    {
        $rounded = $this->roundScore($rawScore);

        if ($rounded === null) {
            return [
                'code' => 'empty',
                'label' => 'Chưa có',
                'rounded' => null,
            ];
        }

        // Rules based on rounded score:
        // < 4.0 => fail
        // 4.0 <= score < 5.0 => warning (risk)
        // >= 5.0 => pass
        if ($rounded >= 5.0) {
            return ['code' => 'passed', 'label' => 'Đạt', 'rounded' => $rounded];
        }
        if ($rounded >= 4.0) {
            return ['code' => 'warning', 'label' => 'Nguy cơ', 'rounded' => $rounded];
        }

        return ['code' => 'failed', 'label' => 'Không đạt', 'rounded' => $rounded];
    }

    public function calculateFinalScore(array $components, array $scoreByComponentId): array
    {
        // Components can come from DB (objects/arrays) and may contain weight_percent or weight.
        // scoreByComponentId: [component_id => score|null]

        $hasAnyScore = false;
        $sum = 0.0;
        $weightSum = 0.0;

        foreach ($components as $component) {
            $componentId = (int) ($component['component_id'] ?? $component->component_id ?? 0);
            if ($componentId <= 0) {
                continue;
            }

            $weightPercent = $component['weight_percent'] ?? $component['weight'] ?? $component->weight_percent ?? $component->weight ?? 0;
            $weightPercent = is_numeric($weightPercent) ? (float) $weightPercent : 0.0;

            // Support weight stored as ratio (0..1) or percent (0..100)
            if ($weightPercent > 0 && $weightPercent <= 1.0) {
                $weightPercent = $weightPercent * 100.0;
            }

            if ($weightPercent <= 0) {
                continue;
            }

            $weightRatio = $weightPercent / 100.0;
            $weightSum += $weightRatio;

            $rawScore = $scoreByComponentId[$componentId] ?? null;
            $score = (is_numeric($rawScore) ? (float) $rawScore : null);

            if ($score !== null) {
                $hasAnyScore = true;
            }

            // Treat missing as 0 so partial grading can still show a total.
            $sum += ($score ?? 0.0) * $weightRatio;
        }

        if (!$hasAnyScore) {
            return [
                'raw' => null,
                'rounded' => null,
                'status' => $this->evaluateScore(null),
            ];
        }

        // If weights are not normalized to 1.0, keep current semantics (no renormalization).
        $raw = $sum;
        $status = $this->evaluateScore($raw);

        return [
            'raw' => $raw,
            'rounded' => $status['rounded'],
            'status' => [
                'code' => $status['code'],
                'label' => $status['label'],
            ],
        ];
    }

    public function evaluateFinalStatus(?float $finalScore): array
    {
        $status = $this->evaluateScore($finalScore);
        return [
            'code' => $status['code'],
            'label' => $status['label'],
        ];
    }

    public function passFail(?float $finalScore): ?string
    {
        $rounded = $this->roundScore($finalScore);
        if ($rounded === null) {
            return null;
        }
        return ($rounded >= 4.0) ? 'pass' : 'fail';
    }

    public function warningLabel(?float $finalScore): ?string
    {
        $rounded = $this->roundScore($finalScore);
        if ($rounded === null) {
            return null;
        }

        // Keep report semantics: show warnings for < 6.0 only.
        if ($rounded < 4.0) {
            return 'Rớt';
        }
        if ($rounded < 5.0) {
            return 'Nguy cơ cao';
        }
        if ($rounded < 6.0) {
            return 'Cảnh báo';
        }
        return null;
    }

    public function scoreBand(?float $finalScore): ?string
    {
        $rounded = $this->roundScore($finalScore);
        if ($rounded === null) {
            return null;
        }

        if ($rounded >= 9.0 && $rounded <= 10.0) return '9_10';
        if ($rounded >= 8.0) return '8_8_9';
        if ($rounded >= 7.0) return '7_7_9';
        if ($rounded >= 6.0) return '6_6_9';
        if ($rounded >= 5.0) return '5_5_9';
        return 'below_5';
    }

    public function calculateAttendancePercent(int $attended, int $total): float
    {
        if ($total <= 0) return 0.0;
        return round(($attended / $total) * 100.0, 2);
    }

    public function evaluateAttendanceEligibility(float $attendancePercent): array
    {
        // Existing lecturer status page semantics
        // >= 80% => eligible
        // < 50% => warning
        // else studying
        if ($attendancePercent >= 80.0) {
            return ['code' => 'eligible', 'label' => 'Đủ điều kiện'];
        }
        if ($attendancePercent < 50.0) {
            return ['code' => 'warning', 'label' => 'Cảnh báo'];
        }
        return ['code' => 'studying', 'label' => 'Đang học'];
    }
}
