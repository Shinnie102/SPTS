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
        'credits',
        'description',
        'status_id',
    ];

    protected $casts = [
        'credits' => 'integer',
    ];

    public function status()
    {
        return $this->belongsTo(CourseStatus::class, 'status_id', 'status_id');
    }

    public function majors()
    {
        return $this->belongsToMany(Major::class, 'major_course', 'course_id', 'major_id');
    }

    public function classSections()
    {
        return $this->hasMany(ClassSection::class, 'course_id', 'course_id');
    }
}
