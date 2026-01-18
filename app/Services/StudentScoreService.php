<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\StudentScore;
use App\Models\Semester;
use Illuminate\Support\Collection;

/**
 * Service xử lý logic điểm số của sinh viên
 */
class StudentScoreService
{
    /**
     * Lấy dữ liệu điểm toàn bộ của sinh viên
     *
     * @param int $studentId
     * @return array
     */
    public function getStudentScores(int $studentId): array
    {
        // Lấy tất cả enrollments với eager loading
        $enrollments = Enrollment::where('student_id', $studentId)
            ->with([
                'classSection.courseVersion.course',
                'classSection.semester.academicYear',
                'classSection.classGradingScheme.gradingScheme.gradingComponents',
                'scores.gradingComponent'
            ])
            ->get();

        if ($enrollments->isEmpty()) {
            return $this->emptyResponse();
        }

        // Xử lý dữ liệu theo semester
        $groupedData = $this->groupBySemester($enrollments);
        
        // Tính tổng quan
        $summary = $this->calculateSummary($groupedData);

        return [
            'summary' => $summary,
            'semesters' => $groupedData
        ];
    }

    /**
     * Nhóm enrollment theo semester
     *
     * @param Collection $enrollments
     * @return array
     */
    protected function groupBySemester(Collection $enrollments): array
    {
        $grouped = [];

        foreach ($enrollments as $enrollment) {
            $classSection = $enrollment->classSection;
            
            if (!$classSection || !$classSection->semester) {
                continue;
            }

            $semester = $classSection->semester;
            $semesterKey = $semester->semester_code;

            if (!isset($grouped[$semesterKey])) {
                // Tạo tên học kỳ đẹp hơn
                $semesterName = $this->formatSemesterName($semester);
                
                // Tạo sort key để sắp xếp đúng (HK1-2024 -> 202401, HK2-2023 -> 202302)
                $sortKey = $this->createSortKey($semesterKey);
                
                // Lấy danh sách components cho semester này
                $classGradingScheme = $classSection->classGradingScheme;
                $components = $classGradingScheme?->gradingScheme?->gradingComponents ?? collect();
                $componentList = $components->map(function($comp) {
                    return [
                        'id' => $comp->component_id,
                        'name' => $comp->component_name,
                        'weight' => $comp->weight_percent
                    ];
                })->toArray();
                
                $grouped[$semesterKey] = [
                    'semester_name' => $semesterName,
                    'sort_key' => $sortKey,
                    'gpa' => 0,
                    'credits' => 0,
                    'components' => $componentList,
                    'courses' => []
                ];
            }

            $courseData = $this->processCourseScore($enrollment);
            
            if ($courseData) {
                $grouped[$semesterKey]['courses'][] = $courseData;
            }
        }

        // Tính GPA và credits cho từng semester
        foreach ($grouped as &$semesterData) {
            $this->calculateSemesterStats($semesterData);
        }

        return $grouped;
    }

    /**
     * Xử lý điểm của 1 môn học
     *
     * @param Enrollment $enrollment
     * @return array|null
     */
    protected function processCourseScore(Enrollment $enrollment): ?array
    {
        $classSection = $enrollment->classSection;
        
        if (!$classSection || !$classSection->courseVersion || !$classSection->courseVersion->course) {
            return null;
        }

        $course = $classSection->courseVersion->course;
        $scores = $enrollment->scores;

        // Lấy grading scheme
        $classGradingScheme = $classSection->classGradingScheme;
        $gradingScheme = $classGradingScheme?->gradingScheme;
        $components = $gradingScheme?->gradingComponents ?? collect();

        // Map điểm theo component
        $scoreByComponent = [];
        $totalWeightedScore = 0;
        $totalWeight = 0;

        foreach ($components as $component) {
            $studentScore = $scores->firstWhere('component_id', $component->component_id);
            
            $score = $studentScore ? (float)$studentScore->score_value : null;
            $scoreByComponent[$component->component_name] = $score;

            if ($score !== null && $component->weight_percent > 0) {
                $totalWeightedScore += $score * $component->weight_percent;
                $totalWeight += $component->weight_percent;
            }
        }

        // Tính điểm tổng kết
        $finalScore = $totalWeight > 0 ? round($totalWeightedScore / $totalWeight, 2) : null;

        // Xác định trạng thái
        $status = $this->determineStatus($finalScore);

        return [
            'class_code' => $classSection->class_code ?? 'N/A',
            'course_code' => $course->course_code ?? 'N/A',
            'course_name' => $course->course_name ?? 'N/A',
            'credits' => $classSection->courseVersion->credit ?? 0,
            'components' => $scoreByComponent,
            'final_score' => $finalScore,
            'grade4' => $this->convertToGrade4($finalScore),
            'letter_grade' => $this->convertToLetterGrade($finalScore),
            'status' => $status
        ];
    }

    /**
     * Quy đổi điểm từ hệ 10 sang hệ 4.0
     *
     * @param float|null $score
     * @return float
     */
    protected function convertToGrade4(?float $score): float
    {
        if ($score === null) return 0;
        if ($score >= 9.0) return 4.0;
        if ($score >= 8.5) return 3.7;
        if ($score >= 8.0) return 3.5;
        if ($score >= 7.0) return 3.0;
        if ($score >= 6.5) return 2.5;
        if ($score >= 5.5) return 2.0;
        if ($score >= 5.0) return 1.5;
        if ($score >= 4.0) return 1.0;
        return 0;
    }

    /**
     * Quy đổi điểm từ hệ 10 sang điểm chữ
     *
     * @param float|null $score
     * @return string
     */
    protected function convertToLetterGrade(?float $score): string
    {
        if ($score === null) return '-';
        if ($score >= 9.0) return 'A+';
        if ($score >= 8.5) return 'A';
        if ($score >= 8.0) return 'B+';
        if ($score >= 7.0) return 'B';
        if ($score >= 6.5) return 'C+';
        if ($score >= 5.5) return 'C';
        if ($score >= 5.0) return 'D+';
        if ($score >= 4.0) return 'D';
        return 'F';
    }

    /**
     * Tính thống kê cho semester
     * GPA tính theo hệ 4.0
     *
     * @param array &$semesterData
     */
    protected function calculateSemesterStats(array &$semesterData): void
    {
        $totalCredits = 0;
        $totalWeightedScore = 0;

        foreach ($semesterData['courses'] as $course) {
            $credits = $course['credits'];
            $finalScore = $course['final_score'];

            if ($finalScore !== null && $credits > 0) {
                $totalCredits += $credits;
                // Quy đổi sang hệ 4.0 trước khi tính
                $grade4 = $this->convertToGrade4($finalScore);
                $totalWeightedScore += $grade4 * $credits;
            }
        }

        $semesterData['credits'] = $totalCredits;
        $semesterData['gpa'] = $totalCredits > 0 
            ? round($totalWeightedScore / $totalCredits, 2) 
            : 0;
    }

    /**
     * Tính tổng quan toàn khóa
     * GPA tính theo hệ 4.0
     *
     * @param array $groupedData
     * @return array
     */
    protected function calculateSummary(array $groupedData): array
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
                    $grade4 = $this->convertToGrade4($finalScore);
                    $totalWeightedScore += $grade4 * $credits;

                    if ($course['status'] === 'Đạt') {
                        $passedCredits += $credits;
                    }
                }
            }
        }

        $gpa = $totalCredits > 0 ? round($totalWeightedScore / $totalCredits, 2) : 0;
        
        // Giả sử tổng tín chỉ cần để tốt nghiệp là 150
        $requiredCredits = 150;
        $progress = $requiredCredits > 0 ? round(($passedCredits / $requiredCredits) * 100, 1) : 0;

        return [
            'gpa' => $gpa,
            'total_credits' => $totalCredits,
            'passed_credits' => $passedCredits,
            'progress' => $progress
        ];
    }

    /**
     * Xác định trạng thái môn học
     *
     * @param float|null $finalScore
     * @return string
     */
    protected function determineStatus(?float $finalScore): string
    {
        if ($finalScore === null) {
            return 'learning'; // Đang học
        }

        if ($finalScore >= 5.0) {
            return 'Đạt';
        } elseif ($finalScore >= 4.0) {
            return 'Đạt có điều kiện';
        } else {
            return 'Không đạt';
        }
    }

    /**
     * Tạo sort key từ semester code để sắp xếp đúng
     * VD: "HK1-2024" -> 202401, "HK2-2023" -> 202302
     *
     * @param string $semesterCode
     * @return int
     */
    protected function createSortKey(string $semesterCode): int
    {
        if (preg_match('/HK(\d+)-(\d+)/', $semesterCode, $matches)) {
            $semesterNumber = $matches[1];
            $year = $matches[2];
            return (int)($year . str_pad($semesterNumber, 2, '0', STR_PAD_LEFT));
        }
        return 0;
    }

    /**
     * Format semester name đẹp hơn
     *
     * @param Semester $semester
     * @return string
     */
    protected function formatSemesterName($semester): string
    {
        $code = $semester->semester_code;
        
        // Semester code format: "HK1-2024" or "HK2-2023"
        if (preg_match('/HK(\d+)-(\d+)/', $code, $matches)) {
            $semesterNumber = $matches[1]; // 1, 2, 3
            $year = $matches[2]; // 2024, 2023
            
            // Nếu có academic year, dùng year_code của nó
            if ($semester->academicYear && $semester->academicYear->year_code) {
                $yearCode = $semester->academicYear->year_code; // "2024-2025"
                return "Học kỳ {$semesterNumber} - Năm {$yearCode}";
            }
            
            // Fallback: tạo year code từ semester code
            $nextYear = (int)$year + 1;
            return "Học kỳ {$semesterNumber} - Năm {$year}-{$nextYear}";
        }
        
        return $code;
    }

    /**
     * Response rỗng
     *
     * @return array
     */
    protected function emptyResponse(): array
    {
        return [
            'summary' => [
                'gpa' => 0,
                'total_credits' => 0,
                'passed_credits' => 0,
                'progress' => 0
            ],
            'semesters' => []
        ];
    }
}
