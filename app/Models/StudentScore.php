<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentScore extends Model
{
    use HasFactory;

    protected $table = 'student_score';
    protected $primaryKey = 'student_score_id';
    
    public $timestamps = false;

    protected $fillable = [
        'enrollment_id',
        'component_id',
        'score_value',
        'last_updated_at',
    ];

    protected $casts = [
        'score_value' => 'decimal:2',
        'last_updated_at' => 'datetime',
    ];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class, 'enrollment_id', 'enrollment_id');
    }

    public function gradingComponent()
    {
        return $this->belongsTo(GradingComponent::class, 'component_id', 'component_id');
    }
}
