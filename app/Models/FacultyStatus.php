<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacultyStatus extends Model
{
    use HasFactory;
    protected $table = 'faculty_status';
    protected $primaryKey = 'status_id';
    public $timestamps = false;
}
