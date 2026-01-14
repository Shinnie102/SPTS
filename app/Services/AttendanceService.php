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

        return [
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
            ->with(['classMeeting', 'status'])
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

        $classSection = $enrollment->classSection;
        $course = $classSection->courseVersion->course;
        $semester = $classSection->semester;
        $academicYear = $semester->academicYear;

        // Build detailed attendance records
        $details = [];
        foreach ($attendances as $index => $attendance) {
            $classMeeting = $attendance->classMeeting;
            $details[] = [
                'stt' => $index + 1,
                'date' => $attendance->marked_at ? $attendance->marked_at->format('d/m/Y') : 'N/A',
                'status' => strtolower($attendance->status->code), // present, absent, late
                'note' => $classMeeting ? $classMeeting->note : '' // Thêm note từ class_meeting
            ];
        }

        return [
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
            'status' => $percentage >= 80 ? 'pass' : 'fail',
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
                $grouped[$semesterKey] = [
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
}
