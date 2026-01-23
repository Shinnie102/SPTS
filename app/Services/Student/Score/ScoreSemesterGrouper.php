<?php

namespace App\Services\Student\Score;

use App\Models\Semester;

/**
 * Class ScoreSemesterGrouper
 * 
 * Chịu trách nhiệm group và format semester data
 * 
 */
class ScoreSemesterGrouper
{
    /**
     * Format semester name đẹp hơn
     * 
     * @param Semester|string $semester Semester object hoặc semester code string
     * @return string
     */
    public function formatSemesterName($semester): string
    {
        // Nếu truyền vào là string, sử dụng luôn
        if (is_string($semester)) {
            $code = $semester;
        } else {
            // Nếu là object, lấy semester_code
            $code = $semester->semester_code;
        }
        
        // Semester code format: "HK1-2024" or "HK2-2023"
        if (preg_match('/HK(\d+)-(\d+)/', $code, $matches)) {
            $semesterNumber = $matches[1];
            $year = $matches[2];
            
            // Nếu có academic year object, dùng year_code của nó
            if (!is_string($semester) && $semester->academicYear && $semester->academicYear->year_code) {
                $yearCode = $semester->academicYear->year_code;
                return "Học kỳ {$semesterNumber} - Năm {$yearCode}";
            }
            
            // Fallback: tạo year code từ semester code
            $nextYear = (int)$year + 1;
            return "Học kỳ {$semesterNumber} - Năm {$year}-{$nextYear}";
        }
        
        return $code;
    }

    /**
     * Tạo sort key từ semester code để sắp xếp đúng
     * VD: "HK1-2024" -> 202401, "HK2-2023" -> 202302
     * 
     * @param string $semesterCode
     * @return int
     */
    public function createSortKey(string $semesterCode): int
    {
        if (preg_match('/HK(\d+)-(\d+)/', $semesterCode, $matches)) {
            $semesterNumber = $matches[1];
            $year = $matches[2];
            return (int)($year . str_pad($semesterNumber, 2, '0', STR_PAD_LEFT));
        }
        return 0;
    }
}
