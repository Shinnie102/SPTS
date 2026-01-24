<?php

namespace App\Contracts;

use App\Models\AcademicRule;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface AcademicRuleRepositoryInterface
 * 
 * Interface cho AcademicRule Repository
 * Tuân theo Interface Segregation Principle (I in SOLID)
 * Tuân theo Dependency Inversion Principle (D in SOLID)
 */
interface AcademicRuleRepositoryInterface
{
    /**
     * Lấy tất cả academic rules (chỉ các rule đang active)
     *
     * @return Collection
     */
    public function getAllActiveRules(): Collection;

    /**
     * Tìm academic rule theo ID
     *
     * @param int $ruleId
     * @return AcademicRule|null
     */
    public function findById(int $ruleId): ?AcademicRule;

    /**
     * Lấy rule theo loại (rule_type)
     *
     * @param string $ruleType
     * @return AcademicRule|null
     */
    public function findByType(string $ruleType): ?AcademicRule;

    /**
     * Lấy tất cả active rules dưới dạng key-value (rule_type => threshold_value)
     * 
     * @return array ['MIN_GPA' => 1.5, 'MIN_ATTENDANCE' => 80, ...]
     */
    public function getActiveRulesKeyValue(): array;
}
