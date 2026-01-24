<?php

namespace App\Repositories;

use App\Contracts\AttendanceRepositoryInterface;
use App\Models\Attendance;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class AttendanceRepository
 * 
 * Repository xử lý data access cho Attendance
 */
class AttendanceRepository implements AttendanceRepositoryInterface
{
    /**
     * Status ID constants
     */
    private const ATTENDANCE_STATUS_PRESENT = 1;

    /**
     * @var Attendance
     */
    protected $model;

    /**
     * Constructor - Dependency Injection
     *
     * @param Attendance $model
     */
    public function __construct(Attendance $model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritDoc}
     */
    public function calculateAttendanceRate(int $enrollmentId): float
    {
        $totalClasses = $this->countTotalClasses($enrollmentId);
        
        if ($totalClasses === 0) {
            return 100.0;
        }

        $presentCount = $this->countPresentClasses($enrollmentId);

        return ($presentCount / $totalClasses) * 100;
    }

    /**
     * {@inheritDoc}
     */
    public function countTotalClasses(int $enrollmentId): int
    {
        return $this->model
            ->where('enrollment_id', $enrollmentId)
            ->count();
    }

    /**
     * {@inheritDoc}
     */
    public function countPresentClasses(int $enrollmentId): int
    {
        return $this->model
            ->where('enrollment_id', $enrollmentId)
            ->where('attendance_status_id', self::ATTENDANCE_STATUS_PRESENT)
            ->count();
    }

    /**
     * {@inheritDoc}
     */
    public function getByEnrollment(int $enrollmentId): Collection
    {
        return $this->model
            ->where('enrollment_id', $enrollmentId)
            ->with(['classMeeting.room', 'classMeeting.timeSlot', 'status'])
            ->orderBy('marked_at')
            ->get();
    }
}
