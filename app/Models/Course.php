<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'course';
    protected $primaryKey = 'course_id';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

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
