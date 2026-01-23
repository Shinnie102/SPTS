<?php

namespace App\Services\Student\Score;

use App\Contracts\EnrollmentRepositoryInterface;
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
     * @var EnrollmentRepositoryInterface
     */
    protected $enrollmentRepository;

    /**
     * @var GradeConverter
     */
    protected $gradeConverter;

    /**
     * @var ScoreCalculator
     */
    protected $calculator;

    /**
     * @var ScoreSemesterGrouper
     */
    protected $grouper;

    /**
     * Constructor - Dependency Injection
     * 
     * @param EnrollmentRepositoryInterface $enrollmentRepository
     * @param GradeConverter $gradeConverter
     * @param ScoreCalculator $calculator
     * @param ScoreSemesterGrouper $grouper
     */
    public function __construct(
        EnrollmentRepositoryInterface $enrollmentRepository,
        GradeConverter $gradeConverter,
        ScoreCalculator $calculator,
        ScoreSemesterGrouper $grouper
    ) {
        $this->enrollmentRepository = $enrollmentRepository;
        $this->gradeConverter = $gradeConverter;
        $this->calculator = $calculator;
        $this->grouper = $grouper;
    }

    /**
     * Lấy dữ liệu điểm toàn bộ của sinh viên
     *
     * @param int $studentId
     * @return array
     */
    public function getStudentScores(int $studentId): array
    {
        // Sử dụng Repository thay vì query trực tiếp
        $enrollments = $this->enrollmentRepository->getAllByStudent($studentId);

        if ($enrollments->isEmpty()) {
            return $this->emptyResponse();
        }

        // Xử lý dữ liệu theo semester
        $groupedData = $this->groupBySemester($enrollments);
        
        // Tính tổng quan (sử dụng ScoreCalculator)
        $summary = $this->calculator->calculateCumulativeGPA($groupedData);

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
                // Sử dụng ScoreSemesterGrouper để format semester name và sort key
                $semesterName = $this->grouper->formatSemesterName($semester);
                $sortKey = $this->grouper->createSortKey($semesterKey);
                
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

        // Tính GPA và credits cho từng semester (sử dụng ScoreCalculator)
        foreach ($grouped as &$semesterData) {
            $semesterData['credits'] = array_sum(array_column($semesterData['courses'], 'credits'));
            $semesterData['gpa'] = $this->calculator->calculateSemesterGPA($semesterData['courses']);
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

        foreach ($components as $component) {
            $studentScore = $scores->firstWhere('component_id', $component->component_id);
            $score = $studentScore ? (float)$studentScore->score_value : null;
            $scoreByComponent[$component->component_name] = $score;
        }

        // Tính điểm tổng kết (sử dụng ScoreCalculator)
        $finalScore = $this->calculator->calculateFinalScore($scoreByComponent, $components);

        // Xác định trạng thái (sử dụng ScoreCalculator)
        $status = $this->calculator->determineStatus($finalScore);

        return [
            'class_code' => $classSection->class_code ?? 'N/A',
            'course_code' => $course->course_code ?? 'N/A',
            'course_name' => $course->course_name ?? 'N/A',
            'credits' => $classSection->courseVersion->credit ?? 0,
            'components' => $scoreByComponent,
            'final_score' => $finalScore,
            'grade4' => $this->gradeConverter->convertToGrade4($finalScore),
            'letter_grade' => $this->gradeConverter->convertToLetterGrade($finalScore),
            'status' => $status
        ];
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
                'progress' => 0,
                'required_credits' => $this->calculator->getRequiredCredits()
            ],
            'semesters' => []
        ];
    }
}
