<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Lecturer\DashboardController as LecturerDashboardController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\studentHistoryController;
use App\Http\Controllers\Lecturer\ClassController; // Thêm dòng này

Route::get('/', function () {
    return view('welcome');
});

// Authentication Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Password Reset Routes (placeholder for now)
Route::post('/password/request-code', function () {
    return response()->json(['success' => false, 'message' => 'Chức năng này chưa được triển khai']);
})->name('password.request.code');

Route::post('/password/verify-code', function () {
    return response()->json(['success' => false, 'message' => 'Chức năng này chưa được triển khai']);
})->name('password.verify.code');

Route::post('/password/reset', function () {
    return response()->json(['success' => false, 'message' => 'Chức năng này chưa được triển khai']);
})->name('password.reset');

// Admin Routes
Route::middleware(['auth', 'role:ADMIN'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // User Management
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('index');

        // API Routes - Static routes MUST come before dynamic routes
        Route::get('/api', [\App\Http\Controllers\Admin\UserController::class, 'getUsers'])->name('api.index');
        Route::get('/api/statistics', [\App\Http\Controllers\Admin\UserController::class, 'statistics'])->name('api.statistics');
        Route::get('/api/download-template', [\App\Http\Controllers\Admin\UserController::class, 'downloadTemplate'])->name('api.downloadTemplate');
        Route::post('/api', [\App\Http\Controllers\Admin\UserController::class, 'store'])->name('api.store');
        Route::post('/api/bulk-import', [\App\Http\Controllers\Admin\UserController::class, 'bulkImport'])->name('api.bulkImport');

        // Dynamic routes with {userId} parameter - MUST be after all static routes
        Route::get('/api/{userId}', [\App\Http\Controllers\Admin\UserController::class, 'show'])->name('api.show');
        Route::put('/api/{userId}', [\App\Http\Controllers\Admin\UserController::class, 'update'])->name('api.update');
        Route::patch('/api/{userId}/toggle-status', [\App\Http\Controllers\Admin\UserController::class, 'toggleStatus'])->name('api.toggleStatus');
        Route::delete('/api/{userId}', [\App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('api.destroy');
    });

    // Quản lý lớp học phần
    Route::get('/lop-hoc', function () {
        return view('admin.adminLophoc');
    })->name('lophoc');
    Route::get('/lop-hoc/tao-buoc-1', function () {
        return view('admin.adminBuoc1Taolophoc');
    })->name('lophoc.create.step1');
    Route::get('/lop-hoc/tao-buoc-2', function () {
        return view('admin.adminBuoc2Taolophoc');
    })->name('lophoc.create.step2');
    Route::get('/lop-hoc/{id}/chi-tiet', function ($id) {
        return view('admin.adminHocphandetail');
    })->name('lophoc.detail');

    // Cấu trúc học thuật
    Route::get('/hoc-thuat', function () {
        return view('admin.adminhocthuat');
    })->name('hocthuat');

    // Quy tắc đánh giá
    Route::get('/quy-tac', function () {
        return view('admin.adminQuytac');
    })->name('quytac');

    // Thời gian học vụ
    Route::get('/thoi-gian', function () {
        return view('admin.adminThoigian');
    })->name('thoigian');
});

// Lecturer Routes
Route::middleware(['auth', 'role:LECTURER'])->prefix('lecturer')->name('lecturer.')->group(function () {
    Route::get('/dashboard', [LecturerDashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [App\Http\Controllers\Lecturer\Profile::class, 'index'])->name('profile');
Route::middleware(['auth', 'role:LECTURER'])->prefix('lecturer')->name('lecturer.')->group(function () {
    Route::get('/dashboard', [LecturerDashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [App\Http\Controllers\Lecturer\Profile::class, 'index'])->name('profile');
    Route::post('/profile/update', [App\Http\Controllers\Lecturer\Profile::class, 'update'])->name('profile.update');


});

    // Lớp học phần - Sử dụng Controller mới
    Route::get('/classes', [ClassController::class, 'index'])->name('classes');
    Route::get('/class/{id}', [ClassController::class, 'show'])->name('class.detail');
    
    // Các route cho từng chức năng của lớp học phần
    Route::get('/class/{id}/attendance', [ClassController::class, 'attendance'])->name('attendance');
    Route::get('/class/{id}/grading', [ClassController::class, 'grading'])->name('grading');
    Route::get('/class/{id}/status', [ClassController::class, 'status'])->name('class.status');
    Route::get('/class/{id}/report', [ClassController::class, 'report'])->name('report');
});

// Student Routes
Route::middleware(['auth', 'role:STUDENT'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [App\Http\Controllers\Student\Profile::class, 'index'])->name('profile');
    Route::get('/study', function () {
        return view('student.studentStudy');
    })->name('study');
    Route::get('/history', [studentHistoryController::class, 'history'])->name('history');
    Route::get('/classes/{class}', function () {
        return 'Class details';
    })->name('classes.show');
});
