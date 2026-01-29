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

        // Semester code format: "HK1", "HK2", "HK3" (không có năm trong code)
        if (preg_match('/HK(\d+)/', $code, $matches)) {
            $semesterNumber = $matches[1];

            // Nếu có academic year object, dùng year_code của nó
            if (!is_string($semester) && $semester->academicYear && $semester->academicYear->year_code) {
                $yearCode = $semester->academicYear->year_code;
                return "Học kỳ {$semesterNumber} - Năm {$yearCode}";
            }

            // Fallback: chỉ hiển thị số học kỳ
            return "Học kỳ {$semesterNumber}";
        }

        return $code;
    }

    /**
     *
     * @param mixed $semester Semester object hoặc semester code string
     * @return float
     */
    public function createSortKey($semester): float
    {
        if (is_string($semester)) {
            $code = $semester;
            // Nếu là string, không thể xác định được academic year, return default
            return 0;
        }

        // Nếu là object, lấy thông tin từ academic year
        if ($semester->academicYear && $semester->academicYear->year_code) {
            // Kiểm tra xem đây có phải học kỳ hiện tại không
            $currentDate = now(); // 29/01/2026

            // Nếu start_date <= current_date <= end_date thì là học kỳ hiện tại
            if ($semester->start_date && $semester->end_date) {
                $startDate = \Carbon\Carbon::parse($semester->start_date);
                $endDate = \Carbon\Carbon::parse($semester->end_date);

                if ($currentDate->between($startDate, $endDate)) {
                    // Học kỳ hiện tại: sort key cao nhất
                    return 9999999999 + $endDate->timestamp;
                }
            }

            // Các học kỳ khác: sắp xếp theo thời gian kết thúc (mới nhất trước)
            if ($semester->end_date) {
                return \Carbon\Carbon::parse($semester->end_date)->timestamp;
            }
        }

        return 0;
    }
}
