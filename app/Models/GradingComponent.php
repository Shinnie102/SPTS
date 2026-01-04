<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradingComponent extends Model
{
    use HasFactory;

    protected $table = 'grading_component';
    protected $primaryKey = 'component_id';
    
    public $timestamps = false;

    protected $fillable = [
        'scheme_id',
        'component_name',
        'weight',
        'max_score',
        'description',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'max_score' => 'decimal:2',
    ];

    public function gradingScheme()
    {
        return $this->belongsTo(GradingScheme::class, 'scheme_id', 'scheme_id');
    }

    public function studentScores()
    {
        return $this->hasMany(StudentScore::class, 'grading_component_id', 'component_id');
    }
}
