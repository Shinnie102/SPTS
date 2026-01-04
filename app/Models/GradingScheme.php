<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GradingScheme extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'grading_scheme';
    protected $primaryKey = 'scheme_id';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

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

    public function components()
    {
        return $this->hasMany(GradingComponent::class, 'scheme_id', 'scheme_id');
    }

    public function classGradingSchemes()
    {
        return $this->hasMany(ClassGradingScheme::class, 'scheme_id', 'scheme_id');
    }
}
