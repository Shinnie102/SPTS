<?php

namespace App\Repositories;

use App\Contracts\AcademicYearRepositoryInterface;
use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Class AcademicYearRepository
 * 
 * Xử lý tất cả logic truy xuất dữ liệu AcademicYear
 * Tuân theo Single Responsibility Principle (S in SOLID)
 */
class AcademicYearRepository implements AcademicYearRepositoryInterface
{
    protected $model;

    public function __construct(AcademicYear $model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritDoc}
     */
    public function getAllWithSemesters(): Collection
    {
        return $this->model->with([
            'status',
            'semesters' => function ($query) {
                $query->with('status')
                      ->orderBy('start_date', 'ASC');
            }
        ])
        ->orderByRaw('CASE WHEN status_id = 1 THEN 0 ELSE 1 END') // ACTIVE trước
        ->orderBy('start_date', 'DESC')
        ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function findById(int $academicYearId): ?AcademicYear
    {
        return $this->model->with(['status', 'semesters.status'])
                           ->find($academicYearId);
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): AcademicYear
    {
        return $this->model->create($data);
    }

    /**
     * {@inheritDoc}
     */
    public function update(int $academicYearId, array $data): bool
    {
        $academicYear = $this->findById($academicYearId);
        
        if (!$academicYear) {
            return false;
        }

        return $academicYear->update($data);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(int $academicYearId): bool
    {
        $academicYear = $this->findById($academicYearId);
        
        if (!$academicYear) {
            return false;
        }

        return $academicYear->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function yearCodeExists(string $yearCode, ?int $excludeId = null): bool
    {
        $query = $this->model->where('year_code', $yearCode);

        if ($excludeId) {
            $query->where('academic_year_id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function countSemesters(int $academicYearId): int
    {
        return DB::table('semester')
                 ->where('academic_year_id', $academicYearId)
                 ->count();
    }

    /**
     * {@inheritDoc}
     */
    public function updateStatusByDate(): void
    {
        $now = Carbon::now()->toDateString();

        // Cập nhật status_id = 1 (ACTIVE) cho năm học đang diễn ra
        DB::table('academic_year')
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->update(['status_id' => 1]);

        // Cập nhật status_id = 2 (COMPLETED) cho năm học đã kết thúc
        DB::table('academic_year')
            ->where('end_date', '<', $now)
            ->update(['status_id' => 2]);
    }
}