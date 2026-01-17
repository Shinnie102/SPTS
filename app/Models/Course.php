<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $table = 'course';
    protected $primaryKey = 'course_id';
    
    public $timestamps = true;

    protected $fillable = [
        'course_code',
        'course_name',
        'course_status_id',
    ];

    public function status()
    {
        return $this->belongsTo(CourseStatus::class, 'course_status_id', 'course_status_id');
    }

    public function majors()
    {
        return $this->belongsToMany(Major::class, 'major_course', 'course_id', 'major_id');
    }

    public function courseVersions()
    {
        return $this->hasMany(CourseVersion::class, 'course_id', 'course_id');
    }
    
    public function latestVersion()
    {
        return $this->hasOne(CourseVersion::class, 'course_id', 'course_id')
                    ->where('status_id', 1)
                    ->latest('effective_from');
    }

    public function classSections()
    {
        return $this->hasMany(ClassSection::class, 'course_id', 'course_id');
    }
}
