<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicYearStatus extends Model
{
    use HasFactory;

    protected $table = 'academic_year_status';
    protected $primaryKey = 'status_id';
    
    public $timestamps = false;

    protected $fillable = [
        'status_code',
        'status_name',
    ];

    public function academicYears()
    {
        return $this->hasMany(AcademicYear::class, 'status_id', 'status_id');
    }
}
