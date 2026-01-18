<?php

namespace App\Repositories;

use App\Contracts\SemesterRepositoryInterface;
use App\Models\Semester;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Class SemesterRepository
 * 
 * Xử lý tất cả logic truy xuất dữ liệu Semester
 * Tuân theo Single Responsibility Principle (S in SOLID)
 */
class SemesterRepository implements SemesterRepositoryInterface
{
    protected $model;

    public function __construct(Semester $model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritDoc}
     */
    public function getByAcademicYear(int $academicYearId): Collection
    {
        return $this->model->where('academic_year_id', $academicYearId)
                           ->with('status')
                           ->orderBy('start_date', 'ASC')
                           ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function findById(int $semesterId): ?Semester
    {
        return $this->model->with(['status', 'academicYear'])
                           ->find($semesterId);
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): Semester
    {
        return $this->model->create($data);
    }

    /**
     * {@inheritDoc}
     */
    public function update(int $semesterId, array $data): bool
    {
        $semester = $this->findById($semesterId);
        
        if (!$semester) {
            return false;
        }

        return $semester->update($data);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(int $semesterId): bool
    {
        $semester = $this->findById($semesterId);
        
        if (!$semester) {
            return false;
        }

        return $semester->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function semesterCodeExists(int $academicYearId, string $semesterCode, ?int $excludeId = null): bool
    {
        $query = $this->model->where('academic_year_id', $academicYearId)
                             ->where('semester_code', $semesterCode);

        if ($excludeId) {
            $query->where('semester_id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function countClassSections(int $semesterId): int
    {
        return DB::table('class_section')
                 ->where('semester_id', $semesterId)
                 ->count();
    }

    /**
     * {@inheritDoc}
     */
    public function updateStatusByDate(): void
    {
        $now = Carbon::now()->toDateString();

        // Cập nhật status_id = 1 (ONGOING) cho học kỳ đang diễn ra
        DB::table('semester')
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->update(['status_id' => 1]);

        // Cập nhật status_id = 2 (COMPLETED) cho học kỳ đã kết thúc
        DB::table('semester')
            ->where('end_date', '<', $now)
            ->update(['status_id' => 2]);
    }
}