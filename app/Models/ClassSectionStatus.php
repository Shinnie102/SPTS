<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassSectionStatus extends Model
{
    use HasFactory;
    protected $table = 'class_section_status';
    protected $primaryKey = 'status_id';
    public $timestamps = false;
}
