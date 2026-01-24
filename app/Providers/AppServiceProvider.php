<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;

use App\Models\ClassModel;
use App\Models\Assignment;
use App\Models\Grade;
use App\Policies\ClassPolicy;
use App\Policies\AssignmentPolicy;
use App\Policies\GradePolicy;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        ClassModel::class => ClassPolicy::class,
        Assignment::class => AssignmentPolicy::class,
        Grade::class => GradePolicy::class,
    ];

    public function register(): void
    {
        $this->app->bind(\App\Contracts\UserRepositoryInterface::class, \App\Repositories\UserRepository::class);
        $this->app->bind(\App\Contracts\FacultyRepositoryInterface::class, \App\Repositories\FacultyRepository::class);
        $this->app->bind(\App\Contracts\MajorRepositoryInterface::class, \App\Repositories\MajorRepository::class);
        $this->app->bind(\App\Contracts\CourseRepositoryInterface::class, \App\Repositories\CourseRepository::class);

        $this->app->bind(\App\Contracts\AcademicYearRepositoryInterface::class, \App\Repositories\AcademicYearRepository::class);
        $this->app->bind(\App\Contracts\SemesterRepositoryInterface::class, \App\Repositories\SemesterRepository::class);

        $this->app->bind(\App\Contracts\GradingSchemeRepositoryInterface::class, \App\Repositories\GradingSchemeRepository::class);
        $this->app->bind(\App\Contracts\AcademicRuleRepositoryInterface::class, \App\Repositories\AcademicRuleRepository::class);

        // Đăng ký Repository cho Student Score, Enrollment, Attendance
        $this->app->bind(\App\Contracts\EnrollmentRepositoryInterface::class, \App\Repositories\EnrollmentRepository::class);
        $this->app->bind(\App\Contracts\AttendanceRepositoryInterface::class, \App\Repositories\AttendanceRepository::class);
        $this->app->bind(\App\Contracts\StudentScoreRepositoryInterface::class, \App\Repositories\StudentScoreRepository::class);

        // Đăng ký Helper Classes cho Student Score Service
        $this->app->singleton(\App\Services\Student\Score\GradeConverter::class);
        $this->app->singleton(\App\Services\Student\Score\ScoreCalculator::class);
        $this->app->singleton(\App\Services\Student\Score\ScoreSemesterGrouper::class);

        // Đăng ký Helper Classes cho Student Warning Service
        $this->app->singleton(\App\Services\Student\Warning\GPAWarningChecker::class);
        $this->app->singleton(\App\Services\Student\Warning\AttendanceWarningChecker::class);
        $this->app->singleton(\App\Services\Student\Warning\FailedCourseWarningChecker::class);

        // Đăng ký Helper Classes cho Student Attendance Service
        $this->app->singleton(\App\Services\Student\Common\AttendanceStatisticsCalculator::class);
        $this->app->singleton(\App\Services\Student\Common\AttendanceStatusDeterminer::class);
    }

 public function boot(): void
{
    foreach ($this->policies as $model => $policy) {
        Gate::policy($model, $policy);
    }

    View::composer('partials.header_admin', function ($view) {

        $notifications = [];

        // 1️⃣ Phân công lớp
        $assignedClasses = DB::table('class_section as cs')
            ->join('class_meeting as cm', 'cm.class_section_id', '=', 'cs.class_section_id')
            ->select('cs.class_code')
            ->orderByDesc('cm.created_at')
            ->limit(3)
            ->get();

        foreach ($assignedClasses as $class) {
            $notifications[] = [
                'type' => 'info',
                'title' => 'Phân công lớp thành công',
                'message' => "Lớp {$class->class_code} đã được gán giảng viên",
            ];
        }

        // 2️⃣ Học kỳ mới
        $semesters = DB::table('semester')
            ->orderByDesc('created_at')
            ->limit(2)
            ->get();

        foreach ($semesters as $sem) {
            $notifications[] = [
                'type' => 'warning',
                'title' => 'Học kỳ mới được tạo',
                'message' => "Học kỳ {$sem->semester_code}",
            ];
        }

        // 3️⃣ Lớp thiếu phân công
        $missingClasses = DB::table('class_section as cs')
            ->leftJoin('class_meeting as cm', 'cm.class_section_id', '=', 'cs.class_section_id')
            ->whereNull('cm.class_meeting_id')
            ->select('cs.class_code')
            ->limit(3)
            ->get();

        foreach ($missingClasses as $class) {
            $notifications[] = [
                'type' => 'danger',
                'title' => 'Có lỗi dữ liệu / thiếu phân công',
                'message' => "Lớp {$class->class_code} chưa có giảng viên phụ trách",
            ];
        }

        // ⭐ trạng thái đã đọc
        $isRead = session()->get('admin_notifications_read', false);

        $view->with([
            'notifications' => $notifications,
            'showBadge' => !$isRead && count($notifications) > 0
        ]);
    });
}

}
