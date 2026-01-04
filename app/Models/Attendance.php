<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'attendance';
    protected $primaryKey = 'attendance_id';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    protected $fillable = [
        'user_id',
        'class_meeting_id',
        'class_section_id',
        'status_id',
        'recorded_by',
        'note',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function classSection()
    {
        return $this->belongsTo(ClassSection::class, 'class_section_id', 'class_section_id');
    }

    public function classMeeting()
    {
        return $this->belongsTo(ClassMeeting::class, 'class_meeting_id', 'meeting_id');
    }

    public function status()
    {
        return $this->belongsTo(AttendanceStatus::class, 'status_id', 'status_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by', 'user_id');
    }
}
