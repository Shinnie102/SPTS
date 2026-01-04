<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Enrollment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'enrollment';
    protected $primaryKey = 'enrollment_id';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    protected $fillable = [
        'user_id',
        'class_section_id',
        'enrollment_date',
        'status_id',
    ];

    protected $casts = [
        'enrollment_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function classSection()
    {
        return $this->belongsTo(ClassSection::class, 'class_section_id', 'class_section_id');
    }

    public function status()
    {
        return $this->belongsTo(EnrollmentStatus::class, 'status_id', 'status_id');
    }
}
