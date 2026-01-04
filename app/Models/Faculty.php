<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Faculty extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'faculty';
    protected $primaryKey = 'faculty_id';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    protected $fillable = [
        'faculty_code',
        'faculty_name',
        'description',
        'campus_id',
        'status_id',
    ];

    public function campus()
    {
        return $this->belongsTo(Campus::class, 'campus_id', 'campus_id');
    }

    public function status()
    {
        return $this->belongsTo(FacultyStatus::class, 'status_id', 'status_id');
    }

    public function majors()
    {
        return $this->belongsToMany(Major::class, 'faculty_major', 'faculty_id', 'major_id');
    }
}
