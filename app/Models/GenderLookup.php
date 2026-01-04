<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GenderLookup extends Model
{
    use HasFactory;

    protected $table = 'gender_lookup';
    protected $primaryKey = 'gender_id';
    
    public $timestamps = false;

    protected $fillable = [
        'gender_code',
        'gender_name',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'gender_id', 'gender_id');
    }
}
