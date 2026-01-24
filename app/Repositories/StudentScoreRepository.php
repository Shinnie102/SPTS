<?php

namespace App\Repositories;

use App\Contracts\StudentScoreRepositoryInterface;
use App\Models\StudentScore;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Class StudentScoreRepository
 * 
 * Repository xử lý data access cho StudentScore
 */
class StudentScoreRepository implements StudentScoreRepositoryInterface
{
    /**
     * Status constants
     */
    private const STATUS_COMPLETED = 2;

    /**
     * Score constants
     */
    private const PASSING_SCORE = 5.0;

    /**
     * @var StudentScore
     */
    protected $model;

    /**
     * Constructor - Dependency Injection
     *
     * @param StudentScore $model
     */
    public function __construct(StudentScore $model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritDoc}
     */
    public function calculateAverageScore(int $enrollmentId): ?float
    {
        $scores = $this->model
            ->where('enrollment_id', $enrollmentId)
            ->whereNotNull('score_value')
            ->get();

        if ($scores->isEmpty()) {
            return null;
        }

        $totalScore = $scores->sum('score_value');
        $count = $scores->count();

        return $totalScore / $count;
    }

    /**
     * {@inheritDoc}
     */
    public function calculateSemesterGPA(int $studentId, int $semesterId): ?float
    {
        return DB::table('student_score')
            ->join('enrollment', 'student_score.enrollment_id', '=', 'enrollment.enrollment_id')
            ->join('class_section', 'enrollment.class_section_id', '=', 'class_section.class_section_id')
            ->where('enrollment.student_id', $studentId)
            ->where('class_section.semester_id', $semesterId)
            ->whereNotNull('student_score.score_value')
            ->avg('student_score.score_value');
    }

    /**
     * {@inheritDoc}
     */
    public function calculateCumulativeGPA(int $studentId): ?float
    {
        return DB::table('student_score')
            ->join('enrollment', 'student_score.enrollment_id', '=', 'enrollment.enrollment_id')
            ->where('enrollment.student_id', $studentId)
            ->where('enrollment.enrollment_status_id', self::STATUS_COMPLETED)
            ->whereNotNull('student_score.score_value')
            ->avg('student_score.score_value');
    }

    /**
     * {@inheritDoc}
     */
    public function getByEnrollment(int $enrollmentId): Collection
    {
        return $this->model
            ->where('enrollment_id', $enrollmentId)
            ->with('gradingComponent')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getFailedCourses(int $studentId): Collection
    {
        return DB::table('enrollment')
            ->join('class_section', 'enrollment.class_section_id', '=', 'class_section.class_section_id')
            ->join('course_version', 'class_section.course_version_id', '=', 'course_version.course_version_id')
            ->join('course', 'course_version.course_id', '=', 'course.course_id')
            ->leftJoin('student_score', 'enrollment.enrollment_id', '=', 'student_score.enrollment_id')
            ->where('enrollment.student_id', $studentId)
            ->where('enrollment.enrollment_status_id', self::STATUS_COMPLETED)
            ->groupBy('enrollment.enrollment_id', 'course.course_name')
            ->havingRaw('AVG(student_score.score_value) < ? OR AVG(student_score.score_value) IS NULL', [self::PASSING_SCORE])
            ->select(
                'course.course_name',
                DB::raw('COALESCE(AVG(student_score.score_value), 0) as avg_score')
            )
            ->get();
    }
}
