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

    /**
     * Xóa một enrollment khỏi lớp, kèm attendance liên quan
     */
    public function deleteEnrollment(int $enrollmentId): void
    {
        DB::transaction(function () use ($enrollmentId) {
            Attendance::where('enrollment_id', $enrollmentId)->delete();
            Enrollment::where('enrollment_id', $enrollmentId)->delete();
        });
    }

    /**
     * Kiểm tra mã lớp đã tồn tại cho các lớp khác (dùng khi update)
     */
    public function getClassCodeExistsForUpdate(string $classCode, int $classSectionId): bool
    {
        return $this->classRepository->classCodeExistsForUpdate($classCode, $classSectionId);
    }

    /**
     * Cập nhật thông tin lớp học (bước 1)
     */
    public function updateStepOne(int $classSectionId, array $data): array
    {
        try {
            DB::beginTransaction();

            // Xử lý meeting_dates array
            $meetingDates = is_array($data['meeting_dates']) ? $data['meeting_dates'] : [$data['meeting_dates']];
            $firstMeetingDate = !empty($meetingDates) ? reset($meetingDates) : null;

            // Cập nhật thông tin lớp học
            $updateData = [
                'class_code' => trim($data['class_code']),
                'course_version_id' => (int) $data['course_version_id'],
                'semester_id' => (int) $data['semester_id'],
                'capacity' => (int) $data['capacity'],
                'time_slot_id' => (int) $data['time_slot_id'],
                'room_id' => (int) $data['room_id'],
                'meeting_date' => $firstMeetingDate,
            ];

            $classSection = ClassSection::findOrFail($classSectionId);
            $classSection->update($updateData);

            // Xóa các buổi học cũ
            ClassMeeting::where('class_section_id', $classSectionId)->delete();

            // Tạo các buổi học mới
            $meetingStatusId = DB::table('meeting_status')->where('code', 'SCHEDULED')->value('status_id') ?? 1;
            foreach ($meetingDates as $date) {
                ClassMeeting::create([
                    'class_section_id' => $classSectionId,
                    'meeting_date' => $date,
                    'time_slot_id' => (int) $data['time_slot_id'],
                    'room_id' => (int) $data['room_id'],
                    'meeting_status_id' => $meetingStatusId,
                ]);
            }

            DB::commit();

            return [
                'class_section_id' => $classSectionId,
                'class_code' => $classSection->class_code,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cập nhật thành viên lớp học (bước 2)
     */
    public function updateStepTwo(int $classSectionId, array $data): array
    {
        try {
            DB::beginTransaction();

            // Cập nhật giảng viên
            $classSection = ClassSection::findOrFail($classSectionId);
            $classSection->update([
                'lecturer_id' => (int) $data['lecturer_id'],
            ]);

            // Xóa các enrollment cũ
            Enrollment::where('class_section_id', $classSectionId)->delete();

            // Tạo enrollment mới cho các sinh viên
            $enrollmentStatusId = DB::table('enrollment_status')->where('code', 'WAITING')->value('status_id') ?? 1;
            $studentIds = $data['student_ids'];
            
            foreach ($studentIds as $studentId) {
                Enrollment::create([
                    'class_section_id' => $classSectionId,
                    'student_id' => (int) $studentId,
                    'enrollment_status_id' => $enrollmentStatusId,
                ]);
            }

            DB::commit();

            return [
                'class_section_id' => $classSectionId,
                'class_code' => $classSection->class_code,
                'total_students' => count($studentIds),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
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
     * Lấy chi tiết lớp học đầy đủ
     * 
     * @param int $classSectionId
     * @return array|null
     */
    public function getClassSectionDetail(int $classSectionId): ?array
    {
        $classSection = $this->classRepository->getDetailedClassSection($classSectionId);

        if (!$classSection) {
            return null;
        }

        // Lấy danh sách buổi học
        $meetings = $this->classRepository->getClassMeetings($classSectionId);

        // Format thông tin ca học (lấy từ buổi học đầu tiên)
        $timeSlotLabel = 'Chưa có lịch';
        $scheduleLabel = 'Chưa có lịch';
        $roomLabel = 'Chưa có phòng';
        
        if (!empty($meetings)) {
            $firstMeeting = $meetings[0];
            
            // Ca học
            if (isset($firstMeeting['time_slot'])) {
                $ts = $firstMeeting['time_slot'];
                $start = substr($ts['start_time'], 0, 5); // HH:MM
                $end = substr($ts['end_time'], 0, 5);
                $timeSlotLabel = "{$start} - {$end}";
            }
            
            // Phòng học (lấy từ buổi đầu tiên)
            if (isset($firstMeeting['room'])) {
                $roomLabel = $firstMeeting['room']['room_name'] ?? $firstMeeting['room']['room_code'];
            }
            
            // Lịch học - Lấy các ngày trong tuần từ meeting_date
            $daysOfWeek = [];
            foreach ($meetings as $meeting) {
                if (isset($meeting['meeting_date'])) {
                    $date = Carbon::parse($meeting['meeting_date']);
                    $dayNum = $date->dayOfWeek; // 0=Sunday, 1=Monday...
                    
                    // Convert to Vietnamese day name
                    $dayName = $this->getDayNameVietnamese($dayNum);
                    if (!in_array($dayName, $daysOfWeek)) {
                        $daysOfWeek[] = $dayName;
                    }
                }
            }
            
            if (!empty($daysOfWeek)) {
                $scheduleLabel = implode(', ', $daysOfWeek);
            }
        }

        // Thông tin năm học
        $academicYearName = $classSection->semester->academicYear->year_code ?? 'N/A';
        $semesterName = $classSection->semester->semester_code ?? 'N/A';

        // Thông tin khoa và ngành (lấy từ course)
        $facultyName = 'N/A';
        $majorName = 'N/A';
        
        if ($classSection->courseVersion && $classSection->courseVersion->course) {
            $course = $classSection->courseVersion->course;
            
            // Lấy major đầu tiên
            if ($course->majors && $course->majors->count() > 0) {
                $major = $course->majors->first();
                $majorName = $major->major_name;
                
                // Lấy faculty từ major
                if ($major->faculties && $major->faculties->count() > 0) {
                    $facultyName = $major->faculties->first()->faculty_name;
                }
            }
        }

        // Thông tin sơ đồ điểm
        $gradingSchemeName = 'Chưa có sơ đồ điểm';
        if ($classSection->classGradingScheme && $classSection->classGradingScheme->gradingScheme) {
            $gradingSchemeName = $classSection->classGradingScheme->gradingScheme->scheme_name;
        }

        // Format danh sách sinh viên
        $students = [];
        $studentCount = 0;
        
        // Kiểm tra xem semester có đang diễn ra hay không
        $semesterIsOngoing = false;
        if ($classSection->semester && $classSection->semester->status) {
            // Semester status codes: ONGOING, COMPLETED, UPCOMING
            $statusCode = strtoupper($classSection->semester->status->code ?? '');
            $semesterIsOngoing = ($statusCode === 'ONGOING');
        }
        
        foreach ($classSection->enrollments as $enrollment) {
            $student = $enrollment->student;
            if (!$student) continue;
            
            $studentCount++;
            
            // Lấy academic status của sinh viên
            $academicStatus = $this->classRepository->getStudentAcademicStatus($student->user_id);
            $academicStatusName = $academicStatus['name'] ?? 'N/A';
            $academicStatusCode = $academicStatus['code'] ?? '';

            // Nếu lớp chưa/không diễn ra (không ONGOING) thì không cảnh cáo theo học vụ chung
            if (!$semesterIsOngoing) {
                $academicStatusName = 'Chưa học';
                $academicStatusCode = 'PENDING';
            }
            
            // Xác định class CSS cho tình trạng
            $statusClass = 'good'; // Mặc định
            if (in_array($academicStatusCode, ['WARNING', 'PROBATION'])) {
                $statusClass = 'bad';
            }
            
            // Enrollment status
            $enrollmentStatusName = $enrollment->status->name ?? 'N/A';
            $enrollmentStatusId = $enrollment->enrollment_status_id;
            
            // Xác định class cho select enrollment status
            // Logic: Nếu status là 1 hoặc 2 (chờ học/đang học) thì check semester
            // Nếu semester đang diễn ra → default "Đang học" (2), còn không → "Chờ học" (1)
            $enrollmentClass = 'studying'; // Mặc định
            if ($enrollmentStatusId == 1 || $enrollmentStatusId == 2) {
                if ($semesterIsOngoing) {
                    $enrollmentClass = 'studying'; // Đang học
                } else {
                    $enrollmentClass = 'warning'; // Chờ học
                }
            } elseif ($enrollmentStatusId == 3) { // DROPPED - stopped
                $enrollmentClass = 'stopped';
            } elseif ($enrollmentStatusId == 4) { // COMPLETED - completed
                $enrollmentClass = 'completed';
            } elseif ($enrollmentStatusId == 5) { // FAILED - failed
                $enrollmentClass = 'failed';
            }

            $students[] = [
                'enrollment_id' => $enrollment->enrollment_id,
                'student_id' => $student->user_id,
                'student_code' => $student->code_user ?? 'N/A',
                'student_name' => $student->full_name ?? 'N/A',
                'faculty_name' => $facultyName,
                'major_name' => $majorName,
                'academic_status_name' => $academicStatusName,
                'academic_status_class' => $statusClass,
                'enrollment_status_name' => $enrollmentStatusName,
                'enrollment_status_id' => $enrollmentStatusId,
                'enrollment_class' => $enrollmentClass,
            ];
        }

        // Lấy thêm các ID phục vụ chế độ chỉnh sửa (edit mode)
        $firstMeeting = $meetings[0] ?? null;
        $majorId = null;
        $facultyId = null;
        if ($classSection->courseVersion && $classSection->courseVersion->course) {
            $course = $classSection->courseVersion->course;
            if ($course->majors && $course->majors->count() > 0) {
                $major = $course->majors->first();
                $majorId = $major->major_id ?? null;
                if ($major->faculties && $major->faculties->count() > 0) {
                    $facultyId = $major->faculties->first()->faculty_id ?? null;
                }
            }
        }

        $classInfo = [
            'class_section_id' => $classSection->class_section_id,
            'class_code' => $classSection->class_code,
            'course_version_id' => $classSection->course_version_id,
            'course_name' => $classSection->courseVersion->course->course_name ?? 'N/A',
            'academic_year_id' => $classSection->semester->academic_year_id ?? null,
            'academic_year_name' => $academicYearName,
            'semester_id' => $classSection->semester_id,
            'semester_name' => $semesterName,
            'faculty_id' => $facultyId,
            'faculty_name' => $facultyName,
            'major_id' => $majorId,
            'major_name' => $majorName,
            'lecturer_id' => $classSection->lecturer_id,
            'lecturer_name' => $classSection->lecturer->full_name ?? 'N/A',
            'status_id' => $classSection->class_section_status_id,
            'status_name' => $classSection->status->name ?? 'N/A',
            'status_code' => $classSection->status->code ?? '',
            'time_slot_id' => $firstMeeting['time_slot_id'] ?? null,
            'time_slot_label' => $timeSlotLabel,
            'room_id' => $firstMeeting['room_id'] ?? null,
            'room_label' => $roomLabel,
            'capacity' => $classSection->capacity ?? 0,
            'meeting_dates' => array_map(fn($m) => $m['meeting_date'], $meetings),
        ];

        return [
            'id' => $classSection->class_section_id,
            'class_code' => $classSection->class_code,
            'course_name' => $classSection->courseVersion->course->course_name ?? 'N/A',
            'academic_year_name' => $academicYearName,
            'semester_name' => $semesterName,
            'faculty_name' => $facultyName,
            'major_name' => $majorName,
            'lecturer_name' => $classSection->lecturer->full_name ?? 'N/A',
            'status_name' => $classSection->status->name ?? 'N/A',
            'status_code' => $classSection->status->code ?? '',
            'time_slot_label' => $timeSlotLabel,
            'schedule_label' => $scheduleLabel,
            'room_label' => $roomLabel,
            'capacity' => $classSection->capacity ?? 0,
            'current_students' => $studentCount,
            'grading_scheme_name' => $gradingSchemeName,
            'students' => $students,
            'meetings' => $meetings,
            'class_info' => $classInfo,
        ];
    }

    /**
     * Chuyển đổi số ngày trong tuần sang tên tiếng Việt
     * 
     * @param int $dayNum (0=Sunday, 1=Monday, ... 6=Saturday)
     * @return string
     */
    private function getDayNameVietnamese(int $dayNum): string
    {
        $days = [
            0 => 'Chủ nhật',
            1 => 'Thứ 2',
            2 => 'Thứ 3',
            3 => 'Thứ 4',
            4 => 'Thứ 5',
            5 => 'Thứ 6',
            6 => 'Thứ 7',
        ];
        
        return $days[$dayNum] ?? 'N/A';
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
