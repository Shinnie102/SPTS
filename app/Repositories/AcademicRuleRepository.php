<?php

namespace App\Repositories;

use App\Contracts\AcademicRuleRepositoryInterface;
use App\Models\AcademicRule;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class AcademicRuleRepository
 * 
 * Xử lý tất cả logic truy xuất dữ liệu AcademicRule
 * Tuân theo Single Responsibility Principle (S in SOLID)
 */
class AcademicRuleRepository implements AcademicRuleRepositoryInterface
{
    protected $model;

    public function __construct(AcademicRule $model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritDoc}
     */
    public function getAllActiveRules(): Collection
    {
        return $this->model->with('status')
                          ->where('status_id', 1) // Chỉ lấy rules ACTIVE
                          ->orderBy('rule_type', 'ASC')
                          ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function findById(int $ruleId): ?AcademicRule
    {
        return $this->model->with('status')->find($ruleId);
    }

    /**
     * {@inheritDoc}
     */
    public function findByType(string $ruleType): ?AcademicRule
    {
        return $this->model->with('status')
                          ->where('rule_type', $ruleType)
                          ->where('status_id', 1)
                          ->first();
    }
}
