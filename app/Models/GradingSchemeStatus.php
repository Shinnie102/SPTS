<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradingSchemeStatus extends Model
{
    use HasFactory;
    protected $table = 'grading_scheme_status';
    protected $primaryKey = 'status_id';
    public $timestamps = false;
}
