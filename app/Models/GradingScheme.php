<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradingScheme extends Model
{
    use HasFactory;

    protected $table = 'grading_scheme';
    protected $primaryKey = 'grading_scheme_id';
    
    public $timestamps = false;

    protected $fillable = [
        'scheme_code',
        'scheme_name',
        'description',
        'status_id',
    ];

    public function status()
    {
        return $this->belongsTo(GradingSchemeStatus::class, 'status_id', 'status_id');
    }

    public function gradingComponents()
    {
        return $this->hasMany(GradingComponent::class, 'grading_scheme_id', 'grading_scheme_id');
    }

    public function classGradingSchemes()
    {
        return $this->hasMany(ClassGradingScheme::class, 'grading_scheme_id', 'grading_scheme_id');
    }
}
