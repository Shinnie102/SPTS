<?php

namespace App\Repositories;

use App\Contracts\ClassSectionRepositoryInterface;
use App\Models\ClassSection;
use App\Models\ClassMeeting;
use App\Models\ClassSectionStatus;
use App\Models\AcademicYear;
use App\Models\CourseVersion;
use App\Models\Faculty;
use App\Models\Enrollment;
use App\Models\Major;
use App\Models\Semester;
use App\Models\Room;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Class ClassSectionRepository
 * 
 * Xử lý tất cả logic truy xuất dữ liệu ClassSection
 * Tuân thủ:
 * - Single Responsibility Principle: Chỉ xử lý database queries
 * - Repository Pattern: Abstraction của data access logic
 * - Dependency Inversion: Phụ thuộc vào interface
 */
class ClassSectionRepository implements ClassSectionRepositoryInterface
{
    protected $model;

    /**
     * Constructor - Dependency Injection
     * 
     * @param ClassSection $model
     */
    public function __construct(ClassSection $model)
    {
        $this->model = $model;
    }

    /**
     * Lấy danh sách lớp học với filter và pagination
     * 
     * Liên kết:
     * ClassSection -> CourseVersion -> Course
     *              -> Semester
     *              -> User (Lecturer)
     *              -> ClassSectionStatus
     * Enrollment -> ClassSection (để đếm sinh viên)
     * 
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginatedClassSections(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model
            ->with([
                'courseVersion.course',
                'semester.status',
                'lecturer',
                'status',
                'enrollments' => function ($q) {
                    // Đếm enrollment có status ACTIVE (1) hoặc COMPLETED (2)
                    $q->whereIn('enrollment_status_id', [1, 2]);
                }
            ]);

        // Filter theo keyword: tìm mã lớp hoặc tên môn học
        if (!empty($filters['keyword'])) {
            $keyword = $filters['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('class_section.class_code', 'LIKE', "%{$keyword}%")
                  ->orWhereHas('courseVersion.course', function ($subQ) use ($keyword) {
                      $subQ->where('course_name', 'LIKE', "%{$keyword}%");
                  });
            });
        }

        // Filter theo Khoa (Faculty) qua liên kết: Course -> Majors -> Faculties (faculty_major)
        if (!empty($filters['faculty_id'])) {
            $facultyId = $filters['faculty_id'];
            $query->whereHas('courseVersion.course.majors.faculties', function ($q) use ($facultyId) {
                $q->where('faculty.faculty_id', $facultyId);
            });
        }

        // Filter theo Chuyên ngành (Major)
        if (!empty($filters['major_id'])) {
            $majorId = $filters['major_id'];
            $query->whereHas('courseVersion.course.majors', function ($q) use ($majorId) {
                $q->where('major.major_id', $majorId);
            });
        }

        // Filter theo Học kỳ (Semester)
        if (!empty($filters['semester_id'])) {
            $query->where('semester_id', $filters['semester_id']);
        }

        // Filter theo Trạng thái
        if (!empty($filters['status_id'])) {
            $query->where('class_section_status_id', $filters['status_id']);
        }

        // Sắp xếp theo tên môn học, tên lớp
        $query->orderBy('class_section.created_at', 'DESC');

        return $query->paginate($perPage);
    }

    /**
     * Lấy chi tiết lớp học theo ID
     * 
     * @param int $classSectionId
     * @return ClassSection|null
     */
    public function findById(int $classSectionId)
    {
        return $this->model
            ->with([
                'courseVersion.course',
                'semester.status',
                'lecturer',
                'status',
                'enrollments.student',
            ])
            ->find($classSectionId);
    }

    /**
     * Lấy chi tiết đầy đủ lớp học với tất cả quan hệ cần thiết cho trang detail
     * 
     * @param int $classSectionId
     * @return ClassSection|null
     */
    public function getDetailedClassSection(int $classSectionId)
    {
        return $this->model
            ->with([
                // Thông tin course
                'courseVersion.course.majors.faculties',
                // Thông tin semester và academic year
                'semester.academicYear',
                'semester.status',
                // Giảng viên
                'lecturer.gender',
                // Trạng thái lớp
                'status',
                // Buổi học với time slot và phòng
                'classGradingScheme.gradingScheme',
                // Enrollment với thông tin sinh viên đầy đủ
                'enrollments' => function ($query) {
                    $query->with([
                        'student.gender',
                        'student.role',
                        'status',
                    ])
                    ->whereIn('enrollment_status_id', [1, 2, 3]) // PENDING, CONFIRMED, DROPPED
                    ->orderBy('enrollment_id', 'ASC');
                },
            ])
            ->find($classSectionId);
    }

    /**
     * Lấy danh sách buổi học của lớp
     * 
     * @param int $classSectionId
     * @return array
     */
    public function getClassMeetings(int $classSectionId): array
    {
        return ClassMeeting::where('class_section_id', $classSectionId)
            ->with(['timeSlot', 'room', 'meetingStatus'])
            ->orderBy('meeting_date', 'ASC')
            ->get()
            ->toArray();
    }

    /**
     * Lấy thông tin academic status của sinh viên
     * 
     * @param int $studentId
     * @return array|null
     */
    public function getStudentAcademicStatus(int $studentId): ?array
    {
        $status = DB::table('academic_status')
            ->join('academic_status_type', 'academic_status.status_code_id', '=', 'academic_status_type.status_id')
            ->where('academic_status.student_id', $studentId)
            ->select('academic_status_type.code', 'academic_status_type.name')
            ->first();

        return $status ? (array) $status : null;
    }

    /**
     * Lấy danh sách Khoa có môn học
     * 
     * Vì DB không có liên kết trực tiếp faculty->major
     * Chúng ta sẽ trả về danh sách tất cả Faculty
     * 
     * @return array
     */
    public function getFacultiesWithCourses(): array
    {
        // Lấy tất cả faculty có môn học trong lớp học đang chạy
        $faculties = Faculty::query()
            ->where('faculty_status_id', 1) // Chỉ lấy faculty Active
            ->orderBy('faculty_name', 'ASC')
            ->get()
            ->map(function ($faculty) {
                return [
                    'id' => $faculty->faculty_id,
                    'name' => $faculty->faculty_name,
                ];
            })
            ->toArray();

        return $faculties;
    }

    /**
     * Lấy danh sách khoa
     * 
     * @return array
     */
    public function getFaculties(): array
    {
        return Faculty::query()
            ->where('faculty_status_id', 1)
            ->orderBy('faculty_name', 'ASC')
            ->get()
            ->map(function ($faculty) {
                return [
                    'id' => $faculty->faculty_id,
                    'name' => $faculty->faculty_name,
                ];
            })
            ->toArray();
    }

    /**
     * Lấy danh sách Chuyên ngành theo Khoa
     * 
     * Vì DB không có link trực tiếp faculty->major
     * Chúng ta lấy các major có môn học trong class_section
     * 
     * @param int $facultyId (không sử dụng do structure)
     * @return array
     */
    public function getMajorsByFaculty(int $facultyId): array
    {
        $majorsQuery = Major::query()->where('major_status_id', 1);

        if ($facultyId > 0) {
            $majorsQuery->whereHas('faculties', function ($q) use ($facultyId) {
                $q->where('faculty.faculty_id', $facultyId);
            });
        }

        $majors = $majorsQuery
            ->orderBy('major_name', 'ASC')
            ->get()
            ->map(function ($major) {
                return [
                    'id' => $major->major_id,
                    'name' => $major->major_name,
                ];
            })
            ->toArray();

        return $majors;
    }

    /**
     * Lấy danh sách Học kỳ
     * 
     * @return array
     */
    public function getSemesters(): array
    {
        return Semester::query()
            ->orderBy('semester_id', 'DESC')
            ->get()
            ->map(function ($semester) {
                return [
                    'id' => $semester->semester_id,
                    'name' => $semester->semester_code,
                ];
            })
            ->toArray();
    }

    /**
     * Lấy danh sách Trạng thái Lớp học
     * 
     * @return array
     */
    public function getStatuses(): array
    {
        return ClassSectionStatus::query()
            ->orderBy('status_id', 'ASC')
            ->get()
            ->map(function ($status) {
                return [
                    'id' => $status->status_id,
                    'code' => $status->code,
                    'name' => $status->name,
                ];
            })
            ->toArray();
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function classCodeExists(string $classCode): bool
    {
        return $this->model->where('class_code', $classCode)->exists();
    }

    /**
     * Kiểm tra mã lớp đã tồn tại cho các lớp khác (dùng khi update)
     */
    public function classCodeExistsForUpdate(string $classCode, int $classSectionId): bool
    {
        return $this->model->where('class_code', $classCode)
            ->where('class_section_id', '!=', $classSectionId)
            ->exists();
    }

    public function createMeetings(array $meetings): void
    {
        if (empty($meetings)) {
            return;
        }

        $now = now();
        foreach ($meetings as &$meeting) {
            $meeting['created_at'] = $meeting['created_at'] ?? $now;
        }

        ClassMeeting::insert($meetings);
    }

    public function enrollStudents(int $classSectionId, array $studentIds, int $statusId = 1): void
    {
        if (empty($studentIds)) {
            return;
        }

        $now = now();
        $rows = array_map(function ($studentId) use ($classSectionId, $statusId, $now) {
            return [
                'student_id' => $studentId,
                'class_section_id' => $classSectionId,
                'enrollment_status_id' => $statusId,
                'enrolled_at' => $now,
                'created_at' => $now,
            ];
        }, $studentIds);

        Enrollment::insert($rows);
    }

    public function getActiveLecturers(): array
    {
        return User::select('user_id as id', 'full_name', 'code_user')
            ->where('role_id', 2) // LECTURER
            ->where('status_id', 1)
            ->orderBy('full_name')
            ->get()
            ->toArray();
    }

    public function getActiveStudents(?string $keyword = null): array
    {
        $query = User::select('user_id as id', 'full_name', 'code_user', 'email', 'major')
            ->where('role_id', 3) // STUDENT
            ->where('status_id', 1)
            ->orderBy('full_name');

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('full_name', 'LIKE', "%{$keyword}%")
                  ->orWhere('code_user', 'LIKE', "%{$keyword}%")
                  ->orWhere('email', 'LIKE', "%{$keyword}%");
            });
        }

        return $query->limit(200)->get()->toArray();
    }

    public function getRooms(): array
    {
        return Room::select('room_id as id', 'room_code', 'room_name', 'capacity')
            ->where('room_status_id', 1)
            ->orderBy('room_code')
            ->get()
            ->toArray();
    }

    public function getTimeSlots(): array
    {
        return TimeSlot::select('time_slot_id as id', 'slot_code', 'start_time', 'end_time')
            ->orderBy('campus_id')
            ->orderBy('start_time')
            ->get()
            ->toArray();
    }

    public function getCourseVersions(): array
    {
        return CourseVersion::with('course')
            ->orderBy('course_version_id', 'ASC')
            ->get()
            ->map(function ($cv) {
                return [
                    'id' => $cv->course_version_id,
                    'name' => $cv->course->course_name ?? 'N/A',
                    'code' => $cv->course->course_code ?? '',
                ];
            })
            ->toArray();
    }

    public function getAcademicYears(): array
    {
        return AcademicYear::query()
            ->orderBy('start_date', 'DESC')
            ->get()
            ->map(function ($year) {
                return [
                    'id' => $year->academic_year_id,
                    // year_code đã có dạng "2023-2024" trong dữ liệu dump
                    'name' => $year->year_code,
                ];
            })
            ->toArray();
    }

    public function getSemestersByAcademicYear(int $academicYearId): array
    {
        return Semester::query()
            ->where('academic_year_id', $academicYearId)
            // Tạm bỏ filter status để load tất cả học kỳ
            // ->where('status_id', 1)
            ->orderBy('start_date')
            ->get()
            ->map(function ($semester) {
                return [
                    'id' => $semester->semester_id,
                    'name' => $semester->semester_code,
                ];
            })
            ->toArray();
    }

    public function getCourseVersionsByMajor(int $majorId): array
    {
        return CourseVersion::query()
            ->join('course', 'course_version.course_id', '=', 'course.course_id')
            ->join('major_course', 'course.course_id', '=', 'major_course.course_id')
            ->where('major_course.major_id', $majorId)
            ->where('course_version.status_id', 1)
            ->where('course.course_status_id', 1)
            ->select('course_version.course_version_id as id', 'course.course_name as name', 'course.course_code as code')
            ->orderBy('course.course_name')
            ->get()
            ->toArray();
    }
}
