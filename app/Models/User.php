<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $table = 'user';
    protected $primaryKey = 'user_id';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    protected $fillable = [
        'code_user',
        'username',
        'password_hash',
        'role_id',
        'full_name',
        'email',
        'phone',
        'address',
        'birth',
        'gender_id',
        'avatar',
        'major',
        'orientation_day',
        'status_id',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'birth' => 'date',
        'orientation_day' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Override password attribute for Laravel Auth
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    // Relationships
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'role_id');
    }

    public function gender()
    {
        return $this->belongsTo(GenderLookup::class, 'gender_id', 'gender_id');
    }

    public function status()
    {
        return $this->belongsTo(UserStatus::class, 'status_id', 'status_id');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'user_id', 'user_id');
    }

    public function studentScores()
    {
        return $this->hasMany(StudentScore::class, 'user_id', 'user_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'user_id', 'user_id');
    }

    // Helper methods
    public function isAdmin()
    {
        return $this->role->role_code === 'admin';
    }

    public function isTeacher()
    {
        return $this->role->role_code === 'teacher';
    }

    public function isStudent()
    {
        return $this->role->role_code === 'student';
    }
}
