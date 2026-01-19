<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingStatus extends Model
{
    use HasFactory;

    protected $table = 'meeting_status';
    protected $primaryKey = 'status_id';

    public $timestamps = false;

    protected $fillable = [
        'code',
        'name',
    ];
}
