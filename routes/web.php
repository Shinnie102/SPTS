<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Lecturer\DashboardController as LecturerDashboardController;
use App\Http\Controllers\Lecturer\AttendanceController;
use App\Http\Controllers\Lecturer\GradingController;
use App\Http\Controllers\Lecturer\ReportController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\studentHistoryController;
use App\Http\Controllers\Student\StudentStudyController;
use App\Http\Controllers\Lecturer\ClassController;
use App\Http\Controllers\Student\PasswordResetController;

/*
|--------------------------------------------------------------------------
| HOME
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| AUTHENTICATION
|--------------------------------------------------------------------------
*/
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| PASSWORD RESET - STUDENT ✅ (KHÔNG auth)
|--------------------------------------------------------------------------
*/
Route::post(
    '/student/password/request-code',
    [PasswordResetController::class, 'requestCode']
)->name('password.request.code');

Route::post(
    '/student/password/verify-code',
    [PasswordResetController::class, 'verifyCode']
)->name('password.verify.code');

Route::post(
    '/student/password/reset',
    [PasswordResetController::class, 'resetPassword']
)->name('password.reset');

/*
|--------------------------------------------------------------------------
| ADMIN
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:ADMIN'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/api/data', [AdminDashboardController::class, 'getDashboardData'])
        ->name('dashboard.api.data');

    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('index');
        Route::get('/api', [\App\Http\Controllers\Admin\UserController::class, 'getUsers'])->name('api.index');
        Route::get('/api/statistics', [\App\Http\Controllers\Admin\UserController::class, 'statistics'])->name('api.statistics');
        Route::get('/api/download-template', [\App\Http\Controllers\Admin\UserController::class, 'downloadTemplate'])->name('api.downloadTemplate');
        Route::post('/api', [\App\Http\Controllers\Admin\UserController::class, 'store'])->name('api.store');
        Route::post('/api/bulk-import', [\App\Http\Controllers\Admin\UserController::class, 'bulkImport'])->name('api.bulkImport');
        Route::get('/api/{userId}', [\App\Http\Controllers\Admin\UserController::class, 'show'])->name('api.show');
        Route::put('/api/{userId}', [\App\Http\Controllers\Admin\UserController::class, 'update'])->name('api.update');
        Route::patch('/api/{userId}/toggle-status', [\App\Http\Controllers\Admin\UserController::class, 'toggleStatus'])->name('api.toggleStatus');
        Route::delete('/api/{userId}', [\App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('api.destroy');
    });

    Route::post('/notifications/read', function () {
        session(['admin_notifications_read' => true]);
        return response()->json(['status' => 'ok']);
    })->name('notifications.read');

});

/*
|--------------------------------------------------------------------------
| LECTURER
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:LECTURER'])->prefix('lecturer')->name('lecturer.')->group(function () {

    Route::get('/dashboard', [LecturerDashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [App\Http\Controllers\Lecturer\Profile::class, 'index'])->name('profile');
    Route::post('/profile/update', [App\Http\Controllers\Lecturer\Profile::class, 'update'])->name('profile.update');

    Route::post('/notifications/mark-all-read', [LecturerDashboardController::class, 'markAllRead'])
        ->name('notifications.markAllRead');

    Route::get('/classes', [ClassController::class, 'index'])->name('classes');
    Route::get('/class/{id}', [ClassController::class, 'show'])->name('class.detail');

    Route::get('/class/{id}/attendance', [AttendanceController::class, 'attendance'])->name('attendance');
    Route::post('/class/{id}/attendance/save', [AttendanceController::class, 'saveAttendance'])->name('attendance.save');

    Route::get('/class/{id}/grading', [GradingController::class, 'grading'])->name('grading');
    Route::post('/class/{id}/grading/save', [GradingController::class, 'saveGrading'])->name('grading.save');

    Route::get('/class/{id}/report', [ReportController::class, 'report'])->name('report');
});

/*
|--------------------------------------------------------------------------
| STUDENT
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:STUDENT'])->prefix('student')->name('student.')->group(function () {

    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/api/data', [StudentDashboardController::class, 'getDashboardData'])
        ->name('dashboard.api.data');

    Route::get('/dashboard/api/gpa-chart', [StudentDashboardController::class, 'getGpaChartData'])
        ->name('dashboard.api.gpaChart');

    Route::get('/profile', [App\Http\Controllers\Student\Profile::class, 'index'])->name('profile');
    Route::post('/profile/update', [App\Http\Controllers\Student\Profile::class, 'update'])->name('profile.update');

    Route::get('/study', [StudentStudyController::class, 'index'])->name('study');
    Route::get('/history', [studentHistoryController::class, 'history'])->name('history');
});
