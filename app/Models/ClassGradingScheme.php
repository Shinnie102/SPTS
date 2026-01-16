<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassGradingScheme extends Model
{
    use HasFactory;
    protected $table = 'class_grading_scheme';
    protected $primaryKey = 'class_grading_scheme_id';
    public $timestamps = false;

    protected $fillable = [
        'class_section_id',
        'grading_scheme_id',
        'applied_at',
        'status_id',
    ];

    protected $casts = [
        'applied_at' => 'datetime',
    ];

    public function classSection()
    {
        return $this->belongsTo(ClassSection::class, 'class_section_id', 'class_section_id');
    }

    public function gradingScheme()
    {
        return $this->belongsTo(GradingScheme::class, 'grading_scheme_id', 'grading_scheme_id');
    }
}
