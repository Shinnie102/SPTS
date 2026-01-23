<?php

namespace App\Services\Student\Score;

/**
 * Class ScoreCalculator
 * 
 * Chịu trách nhiệm tính toán điểm số và GPA
 * Tuân thủ Single Responsibility Principle
 * 
 * Responsibilities:
 * - Tính điểm tổng kết từ các components
 * - Tính GPA theo semester
 * - Tính GPA tổng
 * - Tính progress (tiến độ học tập)
 */
class ScoreCalculator
{
    /**
     * Graduation requirements
     */
    private const REQUIRED_CREDITS = 150;
    private const PASSING_SCORE = 5.0;
    private const CONDITIONAL_PASS_SCORE = 4.0;

    /**
     * @var GradeConverter
     */
    protected $gradeConverter;

    /**
     * Constructor
     * 
     * @param GradeConverter $gradeConverter
     */
    public function __construct(GradeConverter $gradeConverter)
    {
        $this->gradeConverter = $gradeConverter;
    }

    /**
     * Tính điểm tổng kết từ các components với trọng số
     * 
     * @param array $scoreByComponent [component_name => score]
     * @param \Illuminate\Support\Collection $components
     * @return float|null
     */
    public function calculateFinalScore(array $scoreByComponent, $components): ?float
    {
        $totalWeightedScore = 0;
        $totalWeight = 0;

        foreach ($components as $component) {
            $score = $scoreByComponent[$component->component_name] ?? null;

            if ($score !== null && $component->weight_percent > 0) {
                $totalWeightedScore += $score * $component->weight_percent;
                $totalWeight += $component->weight_percent;
            }
        }

        return $totalWeight > 0 ? round($totalWeightedScore / $totalWeight, 2) : null;
    }

    /**
     * Tính GPA cho 1 semester (dựa trên hệ 4.0)
     * 
     * @param array $courses
     * @return float
     */
    public function calculateSemesterGPA(array $courses): float
    {
        $totalCredits = 0;
        $totalWeightedScore = 0;

        foreach ($courses as $course) {
            $credits = $course['credits'] ?? 0;
            $finalScore = $course['final_score'] ?? null;

            if ($finalScore !== null && $credits > 0) {
                $totalCredits += $credits;
                // Quy đổi sang hệ 4.0 trước khi tính
                $grade4 = $this->gradeConverter->convertToGrade4($finalScore);
                $totalWeightedScore += $grade4 * $credits;
            }
        }

        return $totalCredits > 0 
            ? round($totalWeightedScore / $totalCredits, 2) 
            : 0.0;
    }

    /**
     * Tính GPA tổng (cumulative GPA)
     * 
     * @param array $groupedData
     * @return array [gpa, total_credits, passed_credits, progress, required_credits]
     */
    public function calculateCumulativeGPA(array $groupedData): array
    {
        $totalCredits = 0;
        $totalWeightedScore = 0;
        $passedCredits = 0;

        foreach ($groupedData as $semesterData) {
            foreach ($semesterData['courses'] as $course) {
                $credits = $course['credits'];
                $finalScore = $course['final_score'];

                if ($finalScore !== null && $credits > 0) {
                    $totalCredits += $credits;
                    // Quy đổi sang hệ 4.0 trước khi tính
                    $grade4 = $this->gradeConverter->convertToGrade4($finalScore);
                    $totalWeightedScore += $grade4 * $credits;

                    if ($course['status'] === 'Đạt') {
                        $passedCredits += $credits;
                    }
                }
            }
        }

        $gpa = $totalCredits > 0 ? round($totalWeightedScore / $totalCredits, 2) : 0.0;
        
        $progress = self::REQUIRED_CREDITS > 0 
            ? round(($passedCredits / self::REQUIRED_CREDITS) * 100, 1) 
            : 0.0;

        return [
            'gpa' => $gpa,
            'total_credits' => $totalCredits,
            'passed_credits' => $passedCredits,
            'progress' => $progress,
            'required_credits' => self::REQUIRED_CREDITS
        ];
    }

    /**
     * Xác định trạng thái môn học
     * 
     * @param float|null $finalScore
     * @return string
     */
    public function determineStatus(?float $finalScore): string
    {
        if ($finalScore === null) {
            return 'learning';
        }

        if ($finalScore >= self::PASSING_SCORE) {
            return 'Đạt';
        } elseif ($finalScore >= self::CONDITIONAL_PASS_SCORE) {
            return 'Đạt có điều kiện';
        } else {
            return 'Không đạt';
        }
    }

    /**
     * Get required credits constant
     * 
     * @return int
     */
    public function getRequiredCredits(): int
    {
        return self::REQUIRED_CREDITS;
    }
}
