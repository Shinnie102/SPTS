<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassSection extends Model
{
    use HasFactory;

    protected $table = 'class_section';
    protected $primaryKey = 'class_section_id';
    
    public $timestamps = true;

    protected $fillable = [
        'class_code',
        'course_version_id',
        'semester_id',
        'lecturer_id',
        'class_section_status_id',
    ];

    public function courseVersion()
    {
        return $this->belongsTo(CourseVersion::class, 'course_version_id', 'course_version_id');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_id', 'semester_id');
    }

    public function lecturer()
    {
        return $this->belongsTo(User::class, 'lecturer_id', 'user_id');
    }

    public function status()
    {
        return $this->belongsTo(ClassSectionStatus::class, 'class_section_status_id', 'status_id');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'class_section_id', 'class_section_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'class_section_id', 'class_section_id');
    }

    public function gradingSchemes()
    {
        return $this->hasMany(ClassGradingScheme::class, 'class_section_id', 'class_section_id');
    }
    
    // ======== ACCESSOR MỚI (SỬA LẠI) ========
    
    /**
     * Lấy số sinh viên trong lớp (chỉ đếm enrollment hợp lệ)
     * - Trạng thái 1: ACTIVE (Đã đăng ký)
     * - Trạng thái 2: COMPLETED (Đã hoàn thành)
     */
    public function getTotalStudentsAttribute()
    {
        // Đếm số enrollment có trạng thái hợp lệ
        return $this->enrollments()
            ->whereIn('enrollment_status_id', [1, 2]) // ACTIVE hoặc COMPLETED
            ->count();
    }
    
    /**
     * Scope để lấy lớp với số sinh viên (sử dụng subquery)
     */
    public function scopeWithStudentCount($query)
    {
        return $query->addSelect([
            'student_count' => Enrollment::selectRaw('COUNT(*)')
                ->whereColumn('class_section_id', 'class_section.class_section_id')
                ->whereIn('enrollment_status_id', [1, 2])
        ]);
    }
    
    /**
     * Lấy mã môn học
     */
    public function getCourseCodeAttribute()
    {
        return $this->courseVersion->course->course_code ?? 'N/A';
    }
    
    /**
     * Lấy tên môn học
     */
    public function getCourseNameAttribute()
    {
        return $this->courseVersion->course->course_name ?? 'N/A';
    }
    
    /**
     * Lấy mã trạng thái
     */
    public function getStatusCodeAttribute()
    {
        return $this->status->code ?? 'ACTIVE';
    }
    
    /**
     * Lấy tên trạng thái
     */
    public function getStatusNameAttribute()
    {
        return $this->status->name ?? 'Đang giảng dạy';
    }
    
    /**
     * Lấy CSS class cho trạng thái
     */
    public function getStatusClassAttribute()
    {
        $statusCode = strtolower($this->status_code);
        
        $statusMap = [
            'active' => 'status-active',
            'inactive' => 'status-inactive',
            'completed' => 'status-completed',
            'cancelled' => 'status-cancelled',
            'draft' => 'status-draft',
        ];
        
        return $statusMap[$statusCode] ?? 'status-active';
    }
}