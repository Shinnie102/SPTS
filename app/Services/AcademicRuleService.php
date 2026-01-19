<?php

namespace App\Services;

use App\Contracts\AcademicRuleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class AcademicRuleService
 * 
 * Xử lý business logic liên quan đến AcademicRule
 * Tuân thủ Single Responsibility Principle (S in SOLID)
 */
class AcademicRuleService
{
    /**
     * @var AcademicRuleRepositoryInterface
     */
    protected $academicRuleRepository;

    /**
     * Constructor - Dependency Injection
     *
     * @param AcademicRuleRepositoryInterface $academicRuleRepository
     */
    public function __construct(AcademicRuleRepositoryInterface $academicRuleRepository)
    {
        $this->academicRuleRepository = $academicRuleRepository;
    }

    /**
     * Lấy tất cả quy tắc học vụ đang active
     *
     * @return Collection
     */
    public function getAllActiveRules(): Collection
    {
        return $this->academicRuleRepository->getAllActiveRules();
    }

    /**
     * Lấy quy tắc theo loại
     *
     * @param string $ruleType
     * @return mixed
     */
    public function getRuleByType(string $ruleType)
    {
        return $this->academicRuleRepository->findByType($ruleType);
    }

    /**
     * Format quy tắc thành dạng dễ hiển thị
     *
     * @return array
     */
    public function getFormattedRules(): array
    {
        $rules = $this->getAllActiveRules();
        $formatted = [];

        foreach ($rules as $rule) {
            $formatted[] = [
                'rule_id' => $rule->rule_id,
                'rule_type' => $rule->rule_type,
                'threshold_value' => $rule->threshold_value,
                'description' => $rule->description,
                'display_name' => $this->getRuleDisplayName($rule->rule_type, $rule->threshold_value)
            ];
        }

        return $formatted;
    }

    /**
     * Lấy tên hiển thị cho quy tắc
     *
     * @param string $ruleType
     * @param float $thresholdValue
     * @return string
     */
    private function getRuleDisplayName(string $ruleType, float $thresholdValue): string
    {
        return match($ruleType) {
            'MIN_GPA' => "Điểm GPA tối thiểu >= {$thresholdValue}",
            'MIN_ATTENDANCE' => "Điểm danh tối thiểu >= {$thresholdValue}%",
            'MAX_FAILED_COURSES' => "Số môn rớt tối đa <= {$thresholdValue}",
            'GRADUATION_GPA' => "Điểm GPA tốt nghiệp >= {$thresholdValue}",
            default => $ruleType
        };
    }
}
