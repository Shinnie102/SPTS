<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseVersion extends Model
{
    use HasFactory;

    protected $table = 'course_version';
    protected $primaryKey = 'course_version_id';
    
    public $timestamps = false;

    protected $fillable = [
        'course_id',
        'version_no',
        'credit',
        'syllabus',
        'effective_from',
        'effective_to',
        'status_id',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'credit' => 'integer',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    public function status()
    {
        return $this->belongsTo(CourseStatus::class, 'status_id', 'status_id');
    }

    public function classSections()
    {
        return $this->hasMany(ClassSection::class, 'course_version_id', 'course_version_id');
    }
}
