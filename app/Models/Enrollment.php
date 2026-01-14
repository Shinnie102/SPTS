<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;

    protected $table = 'enrollment';
    protected $primaryKey = 'enrollment_id';
    
    public $timestamps = true;

    protected $fillable = [
        'student_id',
        'class_section_id',
        'enrollment_status_id',
        'enrolled_at',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id', 'user_id');
    }

    public function classSection()
    {
        return $this->belongsTo(ClassSection::class, 'class_section_id', 'class_section_id');
    }

    public function status()
    {
        return $this->belongsTo(EnrollmentStatus::class, 'enrollment_status_id', 'status_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'enrollment_id', 'enrollment_id');
    }
}
