<?php

namespace App\Repositories;

use App\Contracts\FacultyRepositoryInterface;
use App\Models\Faculty;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class FacultyRepository
 * 
 * Xử lý tất cả logic truy xuất dữ liệu Faculty
 * Tuân thủ Single Responsibility Principle (S in SOLID)
 */
class FacultyRepository implements FacultyRepositoryInterface
{
    protected $model;

    public function __construct(Faculty $model)
    {
        $this->model = $model;
    }

    public function getPaginatedFaculties(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = $this->model->with(['status', 'majors']);

        // Search theo keyword (tên hoặc mã)
        if (!empty($filters['keyword'])) {
            $keyword = $filters['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('faculty_name', 'LIKE', "%{$keyword}%")
                  ->orWhere('faculty_code', 'LIKE', "%{$keyword}%");
            });
        }

        // Filter theo status
        if (!empty($filters['status'])) {
            $query->where('faculty_status_id', $filters['status']);
        }

        $query->orderBy('created_at', 'DESC');

        return $query->paginate($perPage);
    }

    public function getAllActive()
    {
        return $this->model->where('faculty_status_id', 1)
                           ->orderBy('faculty_name', 'ASC')
                           ->get();
    }

    public function findById(int $facultyId): ?Faculty
    {
        return $this->model->with(['status', 'majors.status'])->find($facultyId);
    }

    public function findByCode(string $code): ?Faculty
    {
        return $this->model->where('faculty_code', $code)
                           ->first();
    }

    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $query = $this->model->where('faculty_code', $code);

        if ($excludeId) {
            $query->where('faculty_id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        $query = $this->model->where('faculty_name', $name);

        if ($excludeId) {
            $query->where('faculty_id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function create(array $data): Faculty
    {
        return $this->model->create($data);
    }

    public function update(int $facultyId, array $data): bool
    {
        $faculty = $this->findById($facultyId);
        
        if (!$faculty) {
            return false;
        }

        return $faculty->update($data);
    }

    public function delete(int $facultyId): bool
    {
        $faculty = $this->findById($facultyId);
        
        if (!$faculty) {
            return false;
        }

        return $faculty->delete();
    }

    public function toggleStatus(int $facultyId, int $statusId): bool
    {
        $faculty = $this->findById($facultyId);
        
        if (!$faculty) {
            return false;
        }

        return $faculty->update(['faculty_status_id' => $statusId]);
    }

    public function countMajors(int $facultyId): int
    {
        $faculty = $this->findById($facultyId);
        
        if (!$faculty) {
            return 0;
        }

        return $faculty->majors()->count();
    }
}