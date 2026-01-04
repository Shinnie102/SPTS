<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnrollmentStatus extends Model
{
    use HasFactory;
    protected $table = 'enrollment_status';
    protected $primaryKey = 'status_id';
    public $timestamps = false;
}
