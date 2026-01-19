<?php

namespace App\Repositories;

use App\Contracts\GradingSchemeRepositoryInterface;
use App\Models\GradingScheme;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Class GradingSchemeRepository
 * 
 * Xử lý tất cả logic truy xuất dữ liệu GradingScheme
 * Tuân theo Single Responsibility Principle (S in SOLID)
 */
class GradingSchemeRepository implements GradingSchemeRepositoryInterface
{
    protected $model;

    public function __construct(GradingScheme $model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritDoc}
     */
    public function getAllWithComponents(): Collection
    {
        return $this->model->with(['status', 'gradingComponents' => function ($query) {
            $query->orderBy('order_no', 'ASC');
        }])
        ->withCount(['classGradingSchemes as classes_count'])
        ->orderBy('created_at', 'DESC')
        ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function findById(int $gradingSchemeId): ?GradingScheme
    {
        return $this->model->with(['status', 'gradingComponents' => function ($query) {
            $query->orderBy('order_no', 'ASC');
        }])
        ->withCount(['classGradingSchemes as classes_count'])
        ->find($gradingSchemeId);
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): GradingScheme
    {
        return DB::transaction(function () use ($data) {
            // Tạo grading scheme
            $gradingScheme = $this->model->create([
                'scheme_code' => $data['scheme_code'],
                'scheme_name' => $data['scheme_name'],
                'description' => $data['description'] ?? null,
                'status_id' => $data['status_id'] ?? 1, // Mặc định ACTIVE
            ]);

            // Tạo components nếu có
            if (!empty($data['components'])) {
                foreach ($data['components'] as $index => $component) {
                    $gradingScheme->gradingComponents()->create([
                        'component_name' => $component['component_name'],
                        'weight_percent' => $component['weight_percent'],
                        'order_no' => $index + 1,
                    ]);
                }
            }

            return $gradingScheme->load('gradingComponents');
        });
    }

    /**
     * {@inheritDoc}
     */
    public function update(int $gradingSchemeId, array $data): bool
    {
        return DB::transaction(function () use ($gradingSchemeId, $data) {
            $gradingScheme = $this->findById($gradingSchemeId);
            
            if (!$gradingScheme) {
                return false;
            }

            // Cập nhật grading scheme
            $gradingScheme->update([
                'scheme_code' => $data['scheme_code'],
                'scheme_name' => $data['scheme_name'],
                'description' => $data['description'] ?? null,
                'status_id' => $data['status_id'] ?? $gradingScheme->status_id,
            ]);

            // Cập nhật components nếu có
            if (isset($data['components'])) {
                $oldComponents = $gradingScheme->gradingComponents;
                $newComponentCount = count($data['components']);
                $oldComponentCount = $oldComponents->count();

                // Cập nhật các components cũ
                foreach ($data['components'] as $index => $component) {
                    if ($index < $oldComponentCount) {
                        // Cập nhật component cũ
                        $oldComponents[$index]->update([
                            'component_name' => $component['component_name'],
                            'weight_percent' => $component['weight_percent'],
                            'order_no' => $index + 1,
                        ]);
                    } else {
                        // Tạo component mới nếu số lượng tăng
                        $gradingScheme->gradingComponents()->create([
                            'component_name' => $component['component_name'],
                            'weight_percent' => $component['weight_percent'],
                            'order_no' => $index + 1,
                        ]);
                    }
                }

                // Chỉ xóa các component thừa nếu số lượng giảm (và không có foreign key)
                if ($newComponentCount < $oldComponentCount) {
                    for ($i = $newComponentCount; $i < $oldComponentCount; $i++) {
                        // Kiểm tra xem component có được sử dụng không trước khi xóa
                        $componentId = $oldComponents[$i]->component_id;
                        $usageCount = DB::table('student_score')
                            ->where('component_id', $componentId)
                            ->count();
                        
                        // Chỉ xóa nếu không có người sử dụng
                        if ($usageCount === 0) {
                            $oldComponents[$i]->delete();
                        }
                    }
                }
            }

            return true;
        });
    }

    /**
     * {@inheritDoc}
     */
    public function delete(int $gradingSchemeId): bool
    {
        return DB::transaction(function () use ($gradingSchemeId) {
            $gradingScheme = $this->findById($gradingSchemeId);
            
            if (!$gradingScheme) {
                return false;
            }

            // Xóa components trước
            $gradingScheme->gradingComponents()->delete();

            // Xóa grading scheme
            return $gradingScheme->delete();
        });
    }

    /**
     * {@inheritDoc}
     */
    public function schemeCodeExists(string $schemeCode, ?int $excludeId = null): bool
    {
        $query = $this->model->where('scheme_code', $schemeCode);

        if ($excludeId) {
            $query->where('grading_scheme_id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function countClassesUsing(int $gradingSchemeId): int
    {
        $gradingScheme = $this->model->withCount('classGradingSchemes')
                                     ->find($gradingSchemeId);

        return $gradingScheme ? $gradingScheme->class_grading_schemes_count : 0;
    }

    /**
     * {@inheritDoc}
     */
    public function isInUse(int $gradingSchemeId): bool
    {
        return $this->countClassesUsing($gradingSchemeId) > 0;
    }
}
