<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Major extends Model
{
    use HasFactory;

    protected $table = 'major';
    protected $primaryKey = 'major_id';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'major_code',
        'major_name',
        'description',
        'major_status_id',
    ];

    public function status()
    {
        return $this->belongsTo(MajorStatus::class, 'major_status_id', 'major_status_id');
    }

    public function faculties()
    {
        return $this->belongsToMany(Faculty::class, 'faculty_major', 'major_id', 'faculty_id');
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'major_course', 'major_id', 'course_id');
    }
}
