<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicYear extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'academic_year';
    protected $primaryKey = 'academic_year_id';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    protected $fillable = [
        'year_code',
        'year_name',
        'start_date',
        'end_date',
        'status_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function status()
    {
        return $this->belongsTo(AcademicYearStatus::class, 'status_id', 'status_id');
    }

    public function semesters()
    {
        return $this->hasMany(Semester::class, 'academic_year_id', 'academic_year_id');
    }
}
