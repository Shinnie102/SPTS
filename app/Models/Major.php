<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Major extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'major';
    protected $primaryKey = 'major_id';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    protected $fillable = [
        'major_code',
        'major_name',
        'description',
        'status_id',
    ];

    public function status()
    {
        return $this->belongsTo(MajorStatus::class, 'status_id', 'status_id');
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
