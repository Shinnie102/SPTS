<?php

namespace App\Services\Student\Common;

/**
 * Class AttendanceStatusDeterminer
 * 
 * Xác định trạng thái attendance dựa trên tỷ lệ phần trăm
 * 
 */
class AttendanceStatusDeterminer
{
    /**
     * Threshold constants
     */
    private const THRESHOLD_PERFECT = 100;
    private const THRESHOLD_WARNING = 80;

    /**
     * Xác định trạng thái attendance dựa trên phần trăm
     * 
     * @param float $percentage
     * @return string 'pass', 'warning', or 'fail'
     */
    public function determineStatus(float $percentage): string
    {
        if ($percentage == self::THRESHOLD_PERFECT) {
            return 'pass';
        } elseif ($percentage >= self::THRESHOLD_WARNING) {
            return 'warning';
        } else {
            return 'fail';
        }
    }

    /**
     * Get threshold values
     * 
     * @return array
     */
    public function getThresholds(): array
    {
        return [
            'perfect' => self::THRESHOLD_PERFECT,
            'warning' => self::THRESHOLD_WARNING,
        ];
    }
}
