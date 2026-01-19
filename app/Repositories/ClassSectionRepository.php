<?php

namespace App\Repositories;

use App\Contracts\ClassSectionRepositoryInterface;
use App\Models\ClassSection;
use App\Models\Faculty;
use App\Models\Major;
use App\Models\Semester;
use App\Models\ClassSectionStatus;
use Illuminate\Pagination\LengthAwarePaginator;

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
}
