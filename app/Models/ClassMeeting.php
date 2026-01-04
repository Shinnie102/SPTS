<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassMeeting extends Model
{
    use HasFactory;
    protected $table = 'class_meeting';
    protected $primaryKey = 'meeting_id';
    
    protected $fillable = [
        'class_section_id',
        'meeting_date',
        'start_time',
        'end_time',
        'room_id',
        'status_id',
    ];

    protected $casts = [
        'meeting_date' => 'date',
    ];

    public function classSection()
    {
        return $this->belongsTo(ClassSection::class, 'class_section_id', 'class_section_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'class_meeting_id', 'meeting_id');
    }
}
