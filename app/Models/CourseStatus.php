<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseStatus extends Model
{
    use HasFactory;
    protected $table = 'course_status';
    protected $primaryKey = 'status_id';
    public $timestamps = false;
}
