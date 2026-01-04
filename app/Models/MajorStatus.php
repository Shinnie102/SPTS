<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MajorStatus extends Model
{
    use HasFactory;
    protected $table = 'major_status';
    protected $primaryKey = 'status_id';
    public $timestamps = false;
}
