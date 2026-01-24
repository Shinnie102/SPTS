<?php

namespace App\Services\Student\Score;
class GradeConverter
{
    /**
     * Grade conversion table - Hệ 10 sang hệ 4.0
     * Format: [min_score => grade_4]
     */
    private const GRADE_4_SCALE = [
        9.0 => 4.0,
        8.5 => 3.7,
        8.0 => 3.5,
        7.0 => 3.0,
        6.5 => 2.5,
        5.5 => 2.0,
        5.0 => 1.5,
        4.0 => 1.0,
        0.0 => 0.0,
    ];

    /**
     * Letter grade conversion table
     * Format: [min_score => letter]
     */
    private const LETTER_GRADES = [
        10.0 => 'A',
        8.5 => 'B+',
        8.0 => 'B',
        7.0 => 'C+',
        6.5 => 'C',
        5.5 => 'D+',
        5.0 => 'D',
        0.0 => 'F',
    ];

    /**
     * Quy đổi điểm từ hệ 10 sang hệ 4.0
     * 
     * Sử dụng Strategy Pattern thông qua lookup table
     *
     * @param float|null $score
     * @return float
     */
    public function convertToGrade4(?float $score): float
    {
        if ($score === null) {
            return 0.0;
        }

        // Sử dụng lookup table thay vì if-else cascade
        foreach (self::GRADE_4_SCALE as $minScore => $grade) {
            if ($score >= $minScore) {
                return $grade;
            }
        }

        return 0.0;
    }

    /**
     * Quy đổi điểm từ hệ 10 sang điểm chữ
     *
     * @param float|null $score
     * @return string
     */
    public function convertToLetterGrade(?float $score): string
    {
        if ($score === null) {
            return '-';
        }
        
        foreach (self::LETTER_GRADES as $minScore => $letter) {
            if ($score >= $minScore) {
                return $letter;
            }
        }

        return 'F';
    }
}
