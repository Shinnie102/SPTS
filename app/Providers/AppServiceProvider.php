<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\ClassModel;
use App\Models\Assignment;
use App\Models\Grade;
use App\Policies\ClassPolicy;
use App\Policies\AssignmentPolicy;
use App\Policies\GradePolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        ClassModel::class => ClassPolicy::class,
        Assignment::class => AssignmentPolicy::class,
        Grade::class => GradePolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Đăng ký Repository Pattern
        // Tuân theo Dependency Inversion Principle (D in SOLID)
        $this->app->bind(\App\Contracts\UserRepositoryInterface::class, \App\Repositories\UserRepository::class);
        $this->app->bind(\App\Contracts\FacultyRepositoryInterface::class, \App\Repositories\FacultyRepository::class);
        $this->app->bind(\App\Contracts\MajorRepositoryInterface::class, \App\Repositories\MajorRepository::class);
        $this->app->bind(\App\Contracts\CourseRepositoryInterface::class, \App\Repositories\CourseRepository::class);
        
        // Đăng ký Repository cho Academic Time
        $this->app->bind(\App\Contracts\AcademicYearRepositoryInterface::class, \App\Repositories\AcademicYearRepository::class);
        $this->app->bind(\App\Contracts\SemesterRepositoryInterface::class, \App\Repositories\SemesterRepository::class);
        
        // Đăng ký Repository cho Grading Scheme và Academic Rule
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

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register policies
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }
}