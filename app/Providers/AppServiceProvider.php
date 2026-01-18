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