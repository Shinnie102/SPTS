<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeSlot extends Model
{
    use HasFactory;

    protected $table = 'time_slot';
    protected $primaryKey = 'time_slot_id';
    public $timestamps = false;

    protected $fillable = [
        'campus_id',
        'slot_code',
        'start_time',
        'end_time',
    ];
}
