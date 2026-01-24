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
    }

    public function boot(): void
    {
        // Register policies
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        /*
        |--------------------------------------------------------------------------
        | VIEW COMPOSER - NOTIFICATION ADMIN
        |--------------------------------------------------------------------------
        */
        View::composer('partials.header_admin', function ($view) {


            $notifications = [];

            // 1️⃣ Phân công lớp thành công
            $assignedClasses = DB::table('class_section as cs')
                ->join('class_meeting as cm', 'cm.class_section_id', '=', 'cs.class_section_id')
                ->select('cs.class_code', 'cm.created_at')
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

            // 2️⃣ Học kỳ mới được tạo
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

            // 3️⃣ Lớp chưa phân công
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

            $view->with('notifications', $notifications);
        });
    }
}
