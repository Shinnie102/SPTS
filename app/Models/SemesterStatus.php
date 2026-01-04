<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SemesterStatus extends Model
{
    use HasFactory;

    protected $table = 'semester_status';
    protected $primaryKey = 'status_id';
    
    public $timestamps = false;

    protected $fillable = [
        'status_code',
        'status_name',
    ];

    public function semesters()
    {
        return $this->hasMany(Semester::class, 'status_id', 'status_id');
    }
}
