<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassSection extends Model
{
    use HasFactory;

    protected $table = 'class_section';
    protected $primaryKey = 'class_section_id';
    
    public $timestamps = true;

    protected $fillable = [
        'class_code',
        'course_version_id',
        'semester_id',
        'lecturer_id',
        'class_section_status_id',
    ];

    public function courseVersion()
    {
        return $this->belongsTo(CourseVersion::class, 'course_version_id', 'course_version_id');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_id', 'semester_id');
    }

    public function lecturer()
    {
        return $this->belongsTo(User::class, 'lecturer_id', 'user_id');
    }

    public function status()
    {
        return $this->belongsTo(ClassSectionStatus::class, 'class_section_status_id', 'status_id');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'class_section_id', 'class_section_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'class_section_id', 'class_section_id');
    }

    public function gradingSchemes()
    {
        return $this->hasMany(ClassGradingScheme::class, 'class_section_id', 'class_section_id');
    }
}
