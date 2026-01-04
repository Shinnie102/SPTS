<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentScore extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'student_score';
    protected $primaryKey = 'score_id';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    protected $fillable = [
        'enrollment_id',
        'user_id',
        'grading_component_id',
        'score',
        'recorded_by',
        'recorded_at',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'recorded_at' => 'datetime',
    ];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class, 'enrollment_id', 'enrollment_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function gradingComponent()
    {
        return $this->belongsTo(GradingComponent::class, 'grading_component_id', 'component_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by', 'user_id');
    }
}
