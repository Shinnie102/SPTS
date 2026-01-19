<?php

namespace App\Services;

use App\Repositories\ClassSectionRepository;
use App\Models\ClassSection;
use App\Models\Enrollment;
use App\Models\StudentScore;
use App\Models\Attendance;
use App\Models\ClassMeeting;
use App\Models\ClassGradingScheme;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class ClassSectionService
 * 
 * Xử lý business logic liên quan đến ClassSection
 * Tuân thủ:
 * - Single Responsibility: Xử lý logic, không xử lý DB
 * - Dependency Inversion: Phụ thuộc vào interface, không vào concrete class
 * - Service Pattern: Tách logic khỏi Controller
 */
class ClassSectionService
{
    protected $classRepository;

    /**
     * Constructor - Dependency Injection
     * 
     * @param ClassSectionRepositoryInterface $classRepository
     */
    public function __construct(ClassSectionRepository $classRepository)
    {
        $this->classRepository = $classRepository;
    }

    /**
     * Lấy danh sách lớp học với filter
     * 
     * @param array $filters
     * @param int $perPage
     * @return array
     */
    public function getFilteredClassSections(array $filters = [], int $perPage = 15): array
    {
        // Gọi repository để lấy dữ liệu
        $classSections = $this->classRepository->getPaginatedClassSections($filters, $perPage);

        // Transform dữ liệu cho view
        $classesFormatted = $classSections->map(function ($classSection) {
            $currentStudents = $classSection->enrollments->count();
            $displayStatus = $this->deriveDisplayStatus($classSection, $currentStudents);

            return [
                'id' => $classSection->class_section_id,
                'class_code' => $classSection->class_code,
                'course_name' => $classSection->courseVersion->course->course_name ?? 'N/A',
                'semester_code' => $classSection->semester->semester_code ?? 'N/A',
                'current_students' => $currentStudents,
                'capacity' => $classSection->capacity,
                'lecturer_name' => $classSection->lecturer->full_name ?? 'N/A',
                'status_name' => $displayStatus['name'],
                'status_id' => $displayStatus['status_id'],
                'status_code' => $displayStatus['code'],
                'badge_class' => $displayStatus['badge'],
            ];
        });

        return [
            'data' => $classesFormatted,
            'pagination' => [
                'current_page' => $classSections->currentPage(),
                'last_page' => $classSections->lastPage(),
                'total' => $classSections->total(),
                'per_page' => $classSections->perPage(),
            ]
        ];
    }

    /**
     * Lấy danh sách filter (Faculty, Major, Semester, Status)
     * 
     * @return array
     */
    public function getFilterOptions(): array
    {
        return [
            'faculties' => $this->classRepository->getFacultiesWithCourses(),
            'majors' => $this->classRepository->getMajorsByFaculty(0), // Lấy tất cả
            'semesters' => $this->classRepository->getSemesters(),
            'statuses' => $this->classRepository->getStatuses(),
        ];
    }

    /**
     * Lấy chi tiết lớp học
     * 
     * @param int $classSectionId
     * @return array|null
     */
    public function getClassSectionDetail(int $classSectionId): ?array
    {
        $classSection = $this->classRepository->findById($classSectionId);

        if (!$classSection) {
            return null;
        }

        $currentStudents = $classSection->enrollments->count();
        $displayStatus = $this->deriveDisplayStatus($classSection, $currentStudents);

        return [
            'id' => $classSection->class_section_id,
            'class_code' => $classSection->class_code,
            'course_name' => $classSection->courseVersion->course->course_name ?? 'N/A',
            'semester_code' => $classSection->semester->semester_code ?? 'N/A',
            'lecturer_name' => $classSection->lecturer->full_name ?? 'N/A',
            'capacity' => $classSection->capacity,
            'current_students' => $currentStudents,
            'status_name' => $displayStatus['name'],
            'status_code' => $displayStatus['code'],
            'badge_class' => $displayStatus['badge'],
            'status_id' => $displayStatus['status_id'],
            'students' => $classSection->enrollments->map(function ($enrollment) {
                return [
                    'student_id' => $enrollment->student_id,
                    'student_name' => $enrollment->student->full_name ?? 'N/A',
                ];
            })->toArray(),
        ];
    }

    /**
     * Tính phần trăm lấp đầy của lớp
     * 
     * @param int $currentStudents
     * @param int $capacity
     * @return float
     */
    public function calculateCapacityPercentage(int $currentStudents, int $capacity): float
    {
        if ($capacity == 0) {
            return 0;
        }
        return round(($currentStudents / $capacity) * 100, 2);
    }

    /**
     * Xác định badge status dựa trên status_id
     * 
     * @param int $statusId
     * @return string
     */
    public function getStatusBadgeClass(int $statusId): string
    {
        return match ($statusId) {
            1 => 'active',      // ONGOING - Đang học
            2 => 'pending',     // COMPLETED - Đã hoàn thành
            3 => 'closed',      // CANCELLED - Đã hủy
            default => 'default',
        };
    }

    /**
     * Suy ra trạng thái hiển thị cho lớp dựa trên trạng thái học kỳ và sức chứa
     */
    protected function deriveDisplayStatus($classSection, int $currentStudents): array
    {
        $capacity = (int) ($classSection->max_students ?? 0);
        $semesterStatusCode = strtoupper($classSection->semester->status->code ?? '');

        // Nếu học kỳ đã kết thúc → COMPLETED / "Đã kết thúc"
        if (in_array($semesterStatusCode, ['COMPLETED', 'ENDED', 'FINISHED'])) {
            return [
                'code' => 'COMPLETED',
                'name' => 'Đã kết thúc',
                'badge' => 'closed',
                'status_id' => 2,
            ];
        }

        // Nếu học kỳ chưa bắt đầu (PLANNED) → lớp là PLANNED (lên kế hoạch)
        if ($semesterStatusCode === 'PLANNED') {
            return [
                'code' => 'PLANNED',
                'name' => 'Lên kế hoạch',
                'badge' => 'warning',
                'status_id' => 3,
            ];
        }

        // Học kỳ đã bắt đầu (STARTED, ON-GOING, ...) → kiểm tra sức chứa
        $isFull = $capacity > 0 && $currentStudents >= $capacity;

        if ($isFull) {
            return [
                'code' => 'CLOSED',
                'name' => 'Đã đủ',
                'badge' => 'closed',
                'status_id' => 3,
            ];
        }

        // Mặc định là OPEN (đang mở) khi học kỳ đang diễn ra và chưa đầy
        return [
            'code' => 'OPEN',
            'name' => 'Đang mở',
            'badge' => 'active',
            'status_id' => 1,
        ];
    }

    /**
     * Xóa cứng một lớp học phần cùng toàn bộ dữ liệu phụ thuộc để tránh xung đột khi tạo lại
     * - Xóa điểm (student_score) theo enrollment
     * - Xóa điểm danh (attendance) theo enrollment và theo class_meeting
     * - Xóa các buổi học (class_meeting)
     * - Xóa sơ đồ điểm áp dụng (class_grading_scheme)
     * - Xóa danh sách đăng ký (enrollment)
     * - Cuối cùng xóa bản ghi class_section
     *
     * @param int $classSectionId
     * @return void
     * @throws \Throwable
     */
    public function deleteClassSection(int $classSectionId): void
    {
        DB::transaction(function () use ($classSectionId) {
            $class = ClassSection::find($classSectionId);
            if (!$class) {
                return; // Không có gì để xóa
            }

            // Lấy các enrollment và class_meeting liên quan
            $enrollmentIds = Enrollment::where('class_section_id', $classSectionId)
                ->pluck('enrollment_id');

            $classMeetingIds = ClassMeeting::where('class_section_id', $classSectionId)
                ->pluck('class_meeting_id');

            if ($enrollmentIds->isNotEmpty()) {
                // Xóa điểm theo enrollment
                StudentScore::whereIn('enrollment_id', $enrollmentIds)->delete();
                // Xóa điểm danh theo enrollment
                Attendance::whereIn('enrollment_id', $enrollmentIds)->delete();
            }

            if ($classMeetingIds->isNotEmpty()) {
                // Xóa điểm danh theo class_meeting (phòng trường hợp có bản ghi chưa gắn enrollment)
                Attendance::whereIn('class_meeting_id', $classMeetingIds)->delete();
                // Xóa các buổi học
                ClassMeeting::whereIn('class_meeting_id', $classMeetingIds)->delete();
            }

            // Xóa lịch học (class_schedule) gắn với lớp
            DB::table('class_schedule')->where('class_section_id', $classSectionId)->delete();

            // Xóa sơ đồ điểm áp dụng cho lớp
            ClassGradingScheme::where('class_section_id', $classSectionId)->delete();

            // Xóa enrollment
            if ($enrollmentIds->isNotEmpty()) {
                Enrollment::whereIn('enrollment_id', $enrollmentIds)->delete();
            }

            // Cuối cùng xóa lớp học phần
            $class->delete();
        });
    }
}
