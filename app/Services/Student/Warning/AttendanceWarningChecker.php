<?php

namespace App\Services\Student\Warning;

use App\Contracts\AttendanceRepositoryInterface;

/**
 * Class AttendanceWarningChecker
 * Xử lý logic cảnh báo chuyên cần
 */
class AttendanceWarningChecker
{
    /**
     * Critical threshold
     */
    private const CRITICAL_ATTENDANCE = 70;

    /**
     * @var AttendanceRepositoryInterface
     */
    protected $attendanceRepository;

    /**
     * Constructor
     * 
     * @param AttendanceRepositoryInterface $attendanceRepository
     */
    public function __construct(AttendanceRepositoryInterface $attendanceRepository)
    {
        $this->attendanceRepository = $attendanceRepository;
    }

    /**
     * Kiểm tra cảnh báo chuyên cần
     * 
     * @param \Illuminate\Database\Eloquent\Collection $enrollments
     * @param float $minAttendanceRate
     * @return array|null
     */
    public function checkAttendance($enrollments, float $minAttendanceRate): ?array
    {
        $lowAttendanceSubjects = [];

        foreach ($enrollments as $enrollment) {
            $attendanceRate = $this->attendanceRepository->calculateAttendanceRate($enrollment->enrollment_id);
            
            if ($attendanceRate < $minAttendanceRate) {
                $courseName = $enrollment->classSection->courseVersion->course->course_name ?? 'N/A';
                
                $lowAttendanceSubjects[] = [
                    'name' => $courseName,
                    'rate' => number_format($attendanceRate, 1) . '%',
                    'attendance_rate' => $attendanceRate
                ];
            }
        }

        if (empty($lowAttendanceSubjects)) {
            return null;
        }

        // Tính severity dựa trên môn có tỷ lệ thấp nhất
        $minRate = min(array_column($lowAttendanceSubjects, 'attendance_rate'));
        $severity = $minRate < self::CRITICAL_ATTENDANCE ? 'high' : 'medium';

        return [
            'type' => 'low_attendance',
            'severity' => $severity,
            'title' => 'Chuyên cần không đạt',
            'description' => 'Bạn có ' . count($lowAttendanceSubjects) . ' môn học với tỷ lệ chuyên cần thấp',
            'subjects' => $lowAttendanceSubjects,
            'threshold' => $minAttendanceRate
        ];
    }
}
