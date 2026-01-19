<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicRule extends Model
{
    use HasFactory;

    protected $table = 'academic_rule';
    protected $primaryKey = 'rule_id';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null; // Không có updated_at trong bảng

    protected $fillable = [
        'rule_type',
        'threshold_value',
        'description',
        'status_id',
    ];

    protected $casts = [
        'threshold_value' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    /**
     * Relationship: Quy tắc thuộc về một trạng thái
     */
    public function status()
    {
        return $this->belongsTo(UserStatus::class, 'status_id', 'status_id');
    }
}
