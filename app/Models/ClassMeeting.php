<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * ClassMeeting Model
 * 
 * Represents a single meeting/session in a class.
 * Foreign Keys:
 * - class_section_id → ClassSection
 * - time_slot_id → TimeSlot
 * - room_id → Room
 * - meeting_status_id → MeetingStatus
 */
class ClassMeeting extends Model
{
    use HasFactory;
    
    protected $table = 'class_meeting';
    protected $primaryKey = 'class_meeting_id';

    public const UPDATED_AT = null;
    
    // Bảng có created_at nhưng KHÔNG có updated_at
    
    protected $fillable = [
        'class_section_id',
        'meeting_date',
        'time_slot_id',
        'room_id',
        'meeting_status_id',
        'note',
        // created_at tự động, không cần thêm vào fillable
    ];

    protected $casts = [
        'meeting_date' => 'date',
        'created_at' => 'datetime',
    ];

    // ===== RELATIONSHIPS =====
    
    /**
     * Relationship: A meeting belongs to a class section
     */
    public function classSection()
    {
        return $this->belongsTo(ClassSection::class, 'class_section_id', 'class_section_id');
    }

    /**
     * Relationship: A meeting has many attendance records
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'class_meeting_id', 'class_meeting_id');
    }

    /**
     * Relationship: A meeting belongs to a time slot
     */
    public function timeSlot()
    {
        return $this->belongsTo(TimeSlot::class, 'time_slot_id', 'time_slot_id');
    }

    /**
     * Relationship: A meeting may have a room assigned
     */
    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'room_id');
    }

    /**
     * Relationship: A meeting has a status
     */
    public function meetingStatus()
    {
        return $this->belongsTo(MeetingStatus::class, 'meeting_status_id', 'meeting_status_id');
    }
}