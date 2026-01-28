<?php

namespace App\Services\Student\Common;

use App\Contracts\AttendanceRepositoryInterface;
use App\Models\Enrollment;

/**
 * Class AttendanceStatisticsCalculator
 * 
 * Tính toán thống kê attendance (present, absent, late, excused)
 * Xây dựng chi tiết attendance records
 * 
 * Trách nhiệm: Calculation logic cho attendance
 */
class AttendanceStatisticsCalculator
{
    /**
     * Attendance status constants
     */
    private const STATUS_PRESENT = 'PRESENT';
    private const STATUS_ABSENT = 'ABSENT';
    private const STATUS_LATE = 'LATE';
    private const STATUS_EXCUSED = 'EXCUSED';

    /**
     * @var AttendanceRepositoryInterface
     */
    protected $attendanceRepository;

    /**
     * Constructor - Dependency Injection
     * 
     * @param AttendanceRepositoryInterface $attendanceRepository
     */
    public function __construct(AttendanceRepositoryInterface $attendanceRepository)
    {
        $this->attendanceRepository = $attendanceRepository;
    }

    /**
     * Calculate attendance statistics for an enrollment
     * 
     * @param Enrollment $enrollment
     * @return array|null
     */
    public function calculateEnrollmentStats(Enrollment $enrollment): ?array
    {
        $attendances = $this->attendanceRepository->getByEnrollment($enrollment->enrollment_id);
        
        if ($attendances->isEmpty()) {
            return null;
        }

        // Đếm số buổi theo status
        $totalSessions = $attendances->count();
        $present = $attendances->filter(fn($a) => $a->status->code === self::STATUS_PRESENT)->count();
        $absent = $attendances->filter(fn($a) => $a->status->code === self::STATUS_ABSENT)->count();
        $late = $attendances->filter(fn($a) => $a->status->code === self::STATUS_LATE)->count();
        $excused = $attendances->filter(fn($a) => $a->status->code === self::STATUS_EXCUSED)->count();

        // Tính phần trăm
        $percentage = ($present / $totalSessions) * 100;

        // Build detailed attendance records
        $details = $this->buildAttendanceDetails($attendances);

        // Lấy thông tin course, semester, year
        $classSection = $enrollment->classSection;
        $course = $classSection?->courseVersion?->course;
        $semester = $classSection?->semester;
        $academicYear = $semester?->academicYear;

        return [
            'class_code' => $classSection?->class_code ?? 'N/A',
            'code' => $course?->course_code ?? 'N/A',
            'name' => $course?->course_name ?? 'N/A',
            'semester' => $semester?->semester_code ?? 'N/A',
            'semesterName' => $semester?->semester_code ?? 'N/A', 
            'year' => $academicYear?->year_code ?? 'N/A',
            'totalSessions' => $totalSessions,
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'excused' => $excused,
            'percentage' => round($percentage, 1),
            'details' => $details
        ];
    }

    /**
     * Build detailed attendance records
     * 
     * @param \Illuminate\Database\Eloquent\Collection $attendances
     * @return array
     */
    protected function buildAttendanceDetails($attendances): array
    {
        $details = [];
        
        foreach ($attendances as $index => $attendance) {
            $classMeeting = $attendance->classMeeting;
            
            // Format time range (e.g., "08:00-10:00")
            $timeRange = '-';
            if ($classMeeting && $classMeeting->timeSlot) {
                $startTime = \Carbon\Carbon::parse($classMeeting->timeSlot->start_time)->format('H:i');
                $endTime = \Carbon\Carbon::parse($classMeeting->timeSlot->end_time)->format('H:i');
                $timeRange = "{$startTime}-{$endTime}";
            }
            
            // Get room code
            $roomCode = '-';
            if ($classMeeting && $classMeeting->room) {
                $roomCode = $classMeeting->room->room_code;
            }
            
            // Get topic/note
            $topic = '-';
            if ($classMeeting && $classMeeting->topic) {
                $topic = $classMeeting->topic;
            } elseif ($classMeeting && $classMeeting->note) {
                $topic = $classMeeting->note;
            }
            
            // Format check-in time 
            $checkInTime = '-';
            if ($attendance->marked_at) {
                $checkInTime = $attendance->marked_at->format('H:i');
            }
            
            $details[] = [
                'stt' => $index + 1,
                'date' => $attendance->marked_at ? $attendance->marked_at->format('d/m/Y') : 'N/A',
                'time' => $timeRange,
                'room' => $roomCode,
                'topic' => $topic,
                'check_in_time' => $checkInTime,
                'status' => strtolower($attendance->status->code), // present, absent, late
            ];
        }

        return $details;
    }

    /**
     * Calculate total progress across all course stats
     * 
     * @param array $courseStats
     * @return float
     */
    public function calculateTotalProgress(array $courseStats): float
    {
        if (empty($courseStats)) {
            return 0;
        }

        $totalPresent = 0;
        $totalSessions = 0;

        foreach ($courseStats as $stat) {
            $totalPresent += $stat['present'];
            $totalSessions += $stat['totalSessions'];
        }

        return $totalSessions > 0 
            ? round(($totalPresent / $totalSessions) * 100, 1) 
            : 0;
    }

    /**
     * Calculate semester progress from courses
     * 
     * @param array $courses
     * @return float
     */
    public function calculateSemesterProgress(array $courses): float
    {
        $totalPresent = 0;
        $totalSessions = 0;
        
        foreach ($courses as $course) {
            $totalPresent += $course['present'];
            $totalSessions += $course['totalSessions'];
        }
        
        return $totalSessions > 0 
            ? round(($totalPresent / $totalSessions) * 100, 1) 
            : 0;
    }
}
