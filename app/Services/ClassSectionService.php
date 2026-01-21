<?php

namespace App\Services;

use App\Repositories\ClassSectionRepository;
use App\Models\ClassSection;
use App\Models\Enrollment;
use App\Models\StudentScore;
use App\Models\Attendance;
use App\Models\ClassMeeting;
use App\Models\ClassGradingScheme;
use App\Models\User;
use App\Models\Room;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Carbon\Carbon;

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
     * Lấy options cho form tạo lớp (Bước 1)
     */
    public function getCreateOptions(): array
    {
        return [
            'faculties' => $this->classRepository->getFacultiesWithCourses(),
            'majors' => $this->classRepository->getMajorsByFaculty(0),
            'semesters' => $this->classRepository->getSemesters(),
            'statuses' => $this->classRepository->getStatuses(),
            'time_slots' => $this->classRepository->getTimeSlots(),
            'rooms' => $this->classRepository->getRooms(),
            'courses' => $this->classRepository->getCourseVersions(),
            'academic_years' => $this->classRepository->getAcademicYears(),
        ];
    }

    /**
     * Lưu dữ liệu bước 1 vào session.
     */
    public function storeStepOne(array $data): array
    {
        // Xử lý meeting_dates array - lấy ngày đầu tiên để lưu vào field meeting_date
        $meetingDates = is_array($data['meeting_dates']) ? $data['meeting_dates'] : [$data['meeting_dates']];
        $firstMeetingDate = !empty($meetingDates) ? reset($meetingDates) : null;

        $payload = [
            'class_code' => trim($data['class_code']),
            'course_version_id' => (int) $data['course_version_id'],
            'semester_id' => (int) $data['semester_id'],
            'capacity' => (int) $data['capacity'],
            'time_slot_id' => (int) $data['time_slot_id'],
            'room_id' => (int) $data['room_id'],
            'meeting_date' => $firstMeetingDate,
            'meeting_dates' => $meetingDates, // Lưu toàn bộ mảng ngày để xử lý sau
            'academic_year_id' => isset($data['academic_year_id']) ? (int) $data['academic_year_id'] : null,
            'faculty_id' => isset($data['faculty_id']) ? (int) $data['faculty_id'] : null,
            'major_id' => isset($data['major_id']) ? (int) $data['major_id'] : null,

            // Labels phục vụ hiển thị tóm tắt bước 2
            'academic_year_name' => $data['academic_year_name'] ?? null,
            'semester_name' => $data['semester_name'] ?? null,
            'faculty_name' => $data['faculty_name'] ?? null,
            'major_name' => $data['major_name'] ?? null,
            'course_name' => $data['course_name'] ?? null,
            'time_slot_label' => $data['time_slot_label'] ?? null,
            'room_name' => $data['room_name'] ?? null,
        ];

        session(['class_section_create_step1' => $payload]);

        return $payload;
    }

    public function getStepOne(): ?array
    {
        return session('class_section_create_step1');
    }

    /**
     * Lấy danh sách học kỳ theo năm học.
     */
    public function getSemestersByAcademicYear(int $academicYearId): array
    {
        return $this->classRepository->getSemestersByAcademicYear($academicYearId);
    }

    /**
     * Lấy danh sách học phần theo chuyên ngành.
     */
    public function getCoursesByMajor(int $majorId): array
    {
        return $this->classRepository->getCourseVersionsByMajor($majorId);
    }

    /**
     * Tạo lớp học, buổi học và enroll sinh viên.
     */
    public function createWithEnrollments(array $data): array
    {
        $step1 = $this->getStepOne();
        if (!$step1) {
            throw new \RuntimeException('Thiếu dữ liệu bước 1. Vui lòng nhập lại.');
        }

        if ($this->classRepository->classCodeExists($step1['class_code'])) {
            throw new \RuntimeException('Mã lớp đã tồn tại.');
        }

        $studentIds = Arr::wrap($data['student_ids'] ?? []);
        $lecturerId = (int) $data['lecturer_id'];

        if (empty($studentIds)) {
            throw new \RuntimeException('Vui lòng chọn ít nhất 1 sinh viên.');
        }

        if ($step1['capacity'] > 0 && count($studentIds) > $step1['capacity']) {
            throw new \RuntimeException('Số lượng sinh viên vượt quá sức chứa đã cấu hình.');
        }

        $lecturer = User::find($lecturerId);
        if (!$lecturer || !$lecturer->isLecturer() || $lecturer->status_id !== 1) {
            throw new \RuntimeException('Giảng viên không hợp lệ hoặc đang bị khóa.');
        }

        $validStudents = User::whereIn('user_id', $studentIds)
            ->where('role_id', 3)
            ->where('status_id', 1)
            ->pluck('user_id')
            ->toArray();
        if (count($validStudents) !== count($studentIds)) {
            throw new \RuntimeException('Danh sách sinh viên chứa bản ghi không hợp lệ.');
        }

        // Kiểm tra sức chứa phòng (nếu có)
        if (!empty($step1['room_id'])) {
            $room = Room::find($step1['room_id']);
            if ($room && $room->capacity && count($studentIds) > $room->capacity) {
                throw new \RuntimeException('Số lượng sinh viên vượt quá sức chứa phòng học đã chọn.');
            }
        }

        return DB::transaction(function () use ($step1, $studentIds, $lecturerId) {
            // Tạo class_section
            $class = $this->classRepository->create([
                'class_code' => $step1['class_code'],
                'course_version_id' => $step1['course_version_id'],
                'semester_id' => $step1['semester_id'],
                'lecturer_id' => $lecturerId,
                'class_section_status_id' => 1, // ONGOING
                'capacity' => $step1['capacity'],
            ]);

            // Tạo một buổi học mặc định dựa trên thông tin bước 1
            $meetingDate = Carbon::parse($step1['meeting_date'])->format('Y-m-d');
            $this->classRepository->createMeetings([
                [
                    'class_section_id' => $class->class_section_id,
                    'meeting_date' => $meetingDate,
                    'time_slot_id' => $step1['time_slot_id'],
                    'room_id' => $step1['room_id'],
                    'meeting_status_id' => 1, // SCHEDULED
                    'note' => 'Buổi học mặc định',
                ]
            ]);

            // Enroll sinh viên
            $this->classRepository->enrollStudents($class->class_section_id, $studentIds, 1); // ACTIVE

            // Clear session
            session()->forget('class_section_create_step1');

            return [
                'class_section_id' => $class->class_section_id,
                'class_code' => $class->class_code,
            ];
        });
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

    public function getClassCodeExists(string $classCode): bool
    {
        return $this->classRepository->classCodeExists($classCode);
    }

    public function getLecturers(): array
    {
        return $this->classRepository->getActiveLecturers();
    }

    public function getStudents(?string $keyword = null): array
    {
        return $this->classRepository->getActiveStudents($keyword);
    }

    /**
     * Lấy danh sách khoa
     */
    public function getFaculties(): array
    {
        return $this->classRepository->getFaculties();
    }

    /**
     * Lấy danh sách chuyên ngành theo khoa
     */
    public function getMajorsByFaculty(int $facultyId): array
    {
        return $this->classRepository->getMajorsByFaculty($facultyId);
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
