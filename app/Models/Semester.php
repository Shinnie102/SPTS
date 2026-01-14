<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Semester extends Model
{
    use HasFactory;

    protected $table = 'semester';
    protected $primaryKey = 'semester_id';
    
    public $timestamps = false;

    protected $fillable = [
        'academic_year_id',
        'semester_code',
        'start_date',
        'end_date',
        'status_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id', 'academic_year_id');
    }

    public function status()
    {
        return $this->belongsTo(SemesterStatus::class, 'status_id', 'status_id');
    }

    public function classSections()
    {
        return $this->hasMany(ClassSection::class, 'semester_id', 'semester_id');
    }
}
