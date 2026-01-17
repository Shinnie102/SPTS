<?php

namespace App\Repositories;

use App\Contracts\MajorRepositoryInterface;
use App\Models\Major;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class MajorRepository
 * 
 * Xử lý tất cả logic truy xuất dữ liệu Major
 * Tuân thủ Single Responsibility Principle (S in SOLID)
 */
class MajorRepository implements MajorRepositoryInterface
{
    protected $model;

    public function __construct(Major $model)
    {
        $this->model = $model;
    }

    public function getPaginatedMajors(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = $this->model->with(['status', 'faculties']);

        // Search theo keyword
        if (!empty($filters['keyword'])) {
            $keyword = $filters['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('major_name', 'LIKE', "%{$keyword}%")
                  ->orWhere('major_code', 'LIKE', "%{$keyword}%");
            });
        }

        // Filter theo faculty
        if (!empty($filters['faculty_id'])) {
            $query->whereHas('faculties', function ($q) use ($filters) {
                $q->where('faculty_id', $filters['faculty_id']);
            });
        }

        $query->orderBy('created_at', 'DESC');

        return $query->paginate($perPage);
    }

    public function getByFacultyId(int $facultyId)
    {
        return $this->model->whereHas('faculties', function ($q) use ($facultyId) {
            $q->where('faculty.faculty_id', $facultyId);
        })->where('major_status_id', 1)->get();
    }

    public function getAllActive()
    {
        return $this->model->where('major_status_id', 1)
                           ->orderBy('major_name', 'ASC')
                           ->get();
    }

    public function findById(int $majorId): ?Major
    {
        return $this->model->with(['status', 'faculties'])->find($majorId);
    }

    public function findByCode(string $code): ?Major
    {
        return $this->model->where('major_code', $code)
                           ->first();
    }

    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $query = $this->model->where('major_code', $code);

        if ($excludeId) {
            $query->where('major_id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        $query = $this->model->where('major_name', $name);

        if ($excludeId) {
            $query->where('major_id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function create(array $data): Major
    {
        return $this->model->create($data);
    }

    public function update(int $majorId, array $data): bool
    {
        $major = $this->findById($majorId);
        
        if (!$major) {
            return false;
        }

        return $major->update($data);
    }

    public function delete(int $majorId): bool
    {
        $major = $this->findById($majorId);
        
        if (!$major) {
            return false;
        }

        return $major->delete();
    }

    public function attachToFaculty(int $majorId, int $facultyId): void
    {
        $major = $this->findById($majorId);
        
        if ($major) {
            $major->faculties()->syncWithoutDetaching([$facultyId]);
        }
    }

    public function detachFromFaculty(int $facultyId): void
    {
        // Xóa tất cả majors khỏi faculty này trong bảng faculty_major
        \DB::table('faculty_major')->where('faculty_id', $facultyId)->delete();
    }
}