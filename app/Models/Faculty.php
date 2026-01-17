<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faculty extends Model
{
    use HasFactory;

    protected $table = 'faculty';
    protected $primaryKey = 'faculty_id';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'faculty_code',
        'faculty_name',
        'description',
        'campus_id',
        'faculty_status_id',
    ];

    public function campus()
    {
        return $this->belongsTo(Campus::class, 'campus_id', 'campus_id');
    }

    public function status()
    {
        return $this->belongsTo(FacultyStatus::class, 'faculty_status_id', 'faculty_status_id');
    }

    public function majors()
    {
        return $this->belongsToMany(Major::class, 'faculty_major', 'faculty_id', 'major_id');
    }
}
