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
        'grading_scheme_id',
        'component_name',
        'weight_percent',
        'order_no',
    ];

    protected $casts = [
        'weight_percent' => 'decimal:2',
    ];

    public function gradingScheme()
    {
        return $this->belongsTo(GradingScheme::class, 'grading_scheme_id', 'grading_scheme_id');
    }

    public function studentScores()
    {
        return $this->hasMany(StudentScore::class, 'component_id', 'component_id');
    }
}
