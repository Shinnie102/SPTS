<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserStatus extends Model
{
    use HasFactory;

    protected $table = 'user_status';
    protected $primaryKey = 'status_id';
    
    public $timestamps = false;

    protected $fillable = [
        'status_code',
        'status_name',
        'description',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'status_id', 'status_id');
    }
}
