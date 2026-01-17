<?php

namespace App\Repositories;

use App\Contracts\CourseRepositoryInterface;
use App\Models\Course;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class CourseRepository
 * 
 * Xử lý tất cả logic truy xuất dữ liệu Course
 * Tuân thủ Single Responsibility Principle (S in SOLID)
 */
class CourseRepository implements CourseRepositoryInterface
{
    protected $model;

    public function __construct(Course $model)
    {
        $this->model = $model;
    }

    public function getPaginatedCourses(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = $this->model->with(['status', 'majors.faculties', 'latestVersion']);

        // Search theo keyword
        if (!empty($filters['keyword'])) {
            $keyword = $filters['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('course_name', 'LIKE', "%{$keyword}%")
                  ->orWhere('course_code', 'LIKE', "%{$keyword}%");
            });
        }

        // Filter theo faculty (thông qua major)
        if (!empty($filters['faculty_id'])) {
            $query->whereHas('majors.faculties', function ($q) use ($filters) {
                $q->where('faculty.faculty_id', $filters['faculty_id']);
            });
        }

        // Filter theo major
        if (!empty($filters['major_id'])) {
            $query->whereHas('majors', function ($q) use ($filters) {
                $q->where('major.major_id', $filters['major_id']);
            });
        }

        // Filter theo status (chỉ ADMIN thấy locked courses)
        if (!empty($filters['show_locked']) && $filters['show_locked'] === false) {
            $query->where('course_status_id', 1); // Chỉ hiển thị active
        }

        $query->orderBy('created_at', 'DESC');

        return $query->paginate($perPage);
    }

    public function findById(int $courseId): ?Course
    {
        return $this->model->with(['status', 'majors.faculties', 'latestVersion'])->find($courseId);
    }

    public function findByCode(string $code): ?Course
    {
        return $this->model->where('course_code', $code)
                           ->first();
    }

    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $query = $this->model->where('course_code', $code);

        if ($excludeId) {
            $query->where('course_id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function create(array $data): Course
    {
        return $this->model->create($data);
    }

    public function update(int $courseId, array $data): bool
    {
        $course = $this->findById($courseId);
        
        if (!$course) {
            return false;
        }

        return $course->update($data);
    }

    public function delete(int $courseId): bool
    {
        $course = $this->findById($courseId);
        
        if (!$course) {
            return false;
        }

        return $course->delete();
    }

    public function toggleLock(int $courseId, int $statusId): bool
    {
        $course = $this->findById($courseId);
        
        if (!$course) {
            return false;
        }

        return $course->update(['course_status_id' => $statusId]);
    }

    public function syncMajors(int $courseId, array $majorIds): void
    {
        $course = $this->findById($courseId);
        
        if ($course) {
            $course->majors()->sync($majorIds);
        }
    }
}