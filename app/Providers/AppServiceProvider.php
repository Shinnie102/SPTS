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
use App\Contracts\UserRepositoryInterface;
use App\Repositories\UserRepository;

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
        // Tuân thủ Dependency Inversion Principle (D in SOLID)
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
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
