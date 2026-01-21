<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Attendance;

class AttendanceService
{
    /**
     * Get attendance summary for a student
     * 
     * @param int $studentId
     * @return array
     */
    public function getStudentAttendanceSummary(int $studentId): array
    {
        // Lấy tất cả enrollments với eager loading relationships
        $enrollments = Enrollment::where('student_id', $studentId)
            ->with([
                'classSection.courseVersion.course',
                'classSection.semester.academicYear',
                'attendances.status'
            ])
            ->get();

        $courseStats = [];

        foreach ($enrollments as $enrollment) {
            $stats = $this->calculateEnrollmentStats($enrollment);
            
            if ($stats) {
                $courseStats[] = $stats;
            }
        }

        // Group by semester và tính progress cho từng semester
        $groupedSemesters = $this->groupBySemesterWithProgress($courseStats);
        
        // Tính tổng progress cho tất cả các môn
        $totalProgress = $this->calculateTotalProgress($groupedSemesters);

        return [
            'totalProgress' => $totalProgress,
            'semesters' => $groupedSemesters
        ];
    }

    /**
     * Calculate attendance statistics for an enrollment
     * 
     * @param Enrollment $enrollment
     * @return array|null
     */
    protected function calculateEnrollmentStats(Enrollment $enrollment): ?array
    {
        $attendances = $enrollment->attendances()
            ->with(['classMeeting.room', 'classMeeting.timeSlot', 'status'])
            ->orderBy('marked_at')
            ->get();
        
        if ($attendances->isEmpty()) {
            return null;
        }

        $totalSessions = $attendances->count();
        $present = $attendances->filter(fn($a) => $a->status->code === 'PRESENT')->count();
        $absent = $attendances->filter(fn($a) => $a->status->code === 'ABSENT')->count();
        $late = $attendances->filter(fn($a) => $a->status->code === 'LATE')->count();

        $percentage = ($present / $totalSessions) * 100;
        
        // Xác định trạng thái: 100% = Đạt, 80% = Cảnh báo, <80% = Không đạt
        if ($percentage == 100) {
            $status = 'pass';
        } elseif ($percentage >= 80) {
            $status = 'warning';
        } else {
            $status = 'fail';
        }

        $classSection = $enrollment->classSection;
        $course = $classSection->courseVersion->course;
        $semester = $classSection->semester;
        $academicYear = $semester->academicYear;

        // Build detailed attendance records
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
            
            // Format check-in time (using marked_at as check-in time)
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

        return [
            'class_code' => $classSection->class_code ?? 'N/A',
            'code' => $course->course_code,
            'name' => $course->course_name,
            'semester' => $semester->semester_code,
            'semesterName' => $semester->semester_code, 
            'year' => $academicYear->year_code,
            'totalSessions' => $totalSessions,
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'percentage' => round($percentage, 1),
            'status' => $status,
            'details' => $details
        ];
    }

    /**
     * Group course stats by semester with progress calculation
     * 
     * @param array $courseStats
     * @return array
     */
    protected function groupBySemesterWithProgress(array $courseStats): array
    {
        $grouped = [];
        
        foreach ($courseStats as $stat) {
            $semesterKey = $stat['semesterName'];
            
            if (!isset($grouped[$semesterKey])) {
                // Format semester name and create sort key
                $semesterName = $this->formatSemesterName($semesterKey);
                $sortKey = $this->createSortKey($semesterKey);
                
                $grouped[$semesterKey] = [
                    'semester_name' => $semesterName,
                    'sort_key' => $sortKey,
                    'progress' => 0,
                    'courses' => []
                ];
            }
            
            $grouped[$semesterKey]['courses'][] = $stat;
        }
        
        // Tính progress cho từng semester
        foreach ($grouped as $semesterKey => &$semesterData) {
            $totalPresent = 0;
            $totalSessions = 0;
            
            foreach ($semesterData['courses'] as $course) {
                $totalPresent += $course['present'];
                $totalSessions += $course['totalSessions'];
            }
            
            $semesterData['progress'] = $totalSessions > 0 
                ? round(($totalPresent / $totalSessions) * 100, 1) 
                : 0;
        }
        
        return $grouped;
    }

    /**
     * Calculate total progress across all semesters
     * 
     * @param array $groupedSemesters
     * @return float
     */
    protected function calculateTotalProgress(array $groupedSemesters): float
    {
        if (empty($groupedSemesters)) {
            return 0;
        }

        $totalPresent = 0;
        $totalSessions = 0;

        foreach ($groupedSemesters as $semesterData) {
            foreach ($semesterData['courses'] as $course) {
                $totalPresent += $course['present'];
                $totalSessions += $course['totalSessions'];
            }
        }

        return $totalSessions > 0 
            ? round(($totalPresent / $totalSessions) * 100, 1) 
            : 0;
    }

    /**
     * Create sort key from semester code for proper sorting
     * e.g., "HK1-2024" -> 202401, "HK2-2023" -> 202302
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
     * Format semester name for display
     * e.g., "HK1-2024" -> "Học kỳ 1 - Năm 2024-2025"
     */
    protected function formatSemesterName(string $semesterCode): string
    {
        if (preg_match('/HK(\d+)-(\d+)/', $semesterCode, $matches)) {
            $semesterNumber = $matches[1];
            $year = $matches[2];
            $nextYear = (int)$year + 1;
            return "Học kỳ {$semesterNumber} - Năm {$year}-{$nextYear}";
        }
        return $semesterCode;
    }
}
