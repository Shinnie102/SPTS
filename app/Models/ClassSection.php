<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassSection extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'class_section';
    protected $primaryKey = 'class_section_id';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    protected $fillable = [
        'class_code',
        'course_id',
        'semester_id',
        'instructor_id',
        'room_id',
        'max_students',
        'current_students',
        'status_id',
    ];

    protected $casts = [
        'max_students' => 'integer',
        'current_students' => 'integer',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_id', 'semester_id');
    }

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id', 'user_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'room_id');
    }

    public function status()
    {
        return $this->belongsTo(ClassSectionStatus::class, 'status_id', 'status_id');
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
