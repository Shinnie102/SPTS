<?php

namespace App\Repositories;

use App\Contracts\EnrollmentRepositoryInterface;
use App\Models\Enrollment;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class EnrollmentRepository
 * 
 * Repository xử lý data access cho Enrollment
 */
class EnrollmentRepository implements EnrollmentRepositoryInterface
{
    /**
     * @var Enrollment
     */
    protected $model;

    /**
     * Constructor - Dependency Injection
     *
     * @param Enrollment $model
     */
    public function __construct(Enrollment $model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritDoc}
     */
    public function getActiveEnrollmentsBySemester(int $studentId, int $semesterId): Collection
    {
        return $this->model
            ->where('student_id', $studentId)
            ->where('enrollment_status_id', 1) // ACTIVE
            ->whereHas('classSection', function($query) use ($semesterId) {
                $query->where('semester_id', $semesterId);
            })
            ->with([
                'classSection.courseVersion.course',
                'classSection.semester'
            ])
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function findByStudentAndClassSection(int $studentId, int $classSectionId)
    {
        return $this->model
            ->where('student_id', $studentId)
            ->where('class_section_id', $classSectionId)
            ->with('classSection.courseVersion.course')
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function getAllByStudent(int $studentId): Collection
    {
        return $this->model
            ->where('student_id', $studentId)
            ->whereHas('classSection.semester.academicYear', function($query) {
                $query->whereIn('status_id', [1, 2]); // ACTIVE or COMPLETED
            })
            ->with([
                'classSection.courseVersion.course',
                'classSection.semester.academicYear',
                'classSection.classGradingScheme.gradingScheme.gradingComponents',
                'scores.gradingComponent'
            ])
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function countByStudentAndStatus(int $studentId, int $statusId): int
    {
        return $this->model
            ->where('student_id', $studentId)
            ->where('enrollment_status_id', $statusId)
            ->count();
    }
}
