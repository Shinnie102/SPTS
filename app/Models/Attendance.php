<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'attendance';
    protected $primaryKey = 'attendance_id';
    
    public $timestamps = false;

    protected $fillable = [
        'enrollment_id',
        'class_meeting_id',
        'attendance_status_id',
        'marked_at',
    ];

    protected $casts = [
        'marked_at' => 'datetime',
    ];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class, 'enrollment_id', 'enrollment_id');
    }

    public function classMeeting()
    {
        return $this->belongsTo(ClassMeeting::class, 'class_meeting_id', 'class_meeting_id');
    }

    public function status()
    {
        return $this->belongsTo(AttendanceStatus::class, 'attendance_status_id', 'status_id');
    }
}
