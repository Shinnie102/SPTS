<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Lecturer\DashboardController as LecturerDashboardController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\studentHistoryController;

Route::get('/', function () {
    return view('welcome');
});

// Authentication Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Password Reset Routes (placeholder for now)
Route::post('/password/request-code', function() {
    return response()->json(['success' => false, 'message' => 'Chức năng này chưa được triển khai']);
})->name('password.request.code');

Route::post('/password/verify-code', function() {
    return response()->json(['success' => false, 'message' => 'Chức năng này chưa được triển khai']);
})->name('password.verify.code');

Route::post('/password/reset', function() {
    return response()->json(['success' => false, 'message' => 'Chức năng này chưa được triển khai']);
})->name('password.reset');

// Admin Routes
Route::middleware(['auth', 'role:ADMIN'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/users', function () { return view('admin.adminUsers'); })->name('users');
    
    // Quản lý lớp học phần
    Route::get('/lop-hoc', function () { return view('admin.adminLophoc'); })->name('lophoc');
    Route::get('/lop-hoc/tao-buoc-1', function () { return view('admin.adminBuoc1Taolophoc'); })->name('lophoc.create.step1');
    Route::get('/lop-hoc/tao-buoc-2', function () { return view('admin.adminBuoc2Taolophoc'); })->name('lophoc.create.step2');
    Route::get('/lop-hoc/{id}/chi-tiet', function ($id) { return view('admin.adminHocphandetail'); })->name('lophoc.detail');
    
    // Cấu trúc học thuật
    Route::get('/hoc-thuat', function () { return view('admin.adminhocthuat'); })->name('hocthuat');
    
    // Quy tắc đánh giá
    Route::get('/quy-tac', function () { return view('admin.adminQuytac'); })->name('quytac');
    
    // Thời gian học vụ
    Route::get('/thoi-gian', function () { return view('admin.adminThoigian'); })->name('thoigian');
});

// Lecturer Routes
Route::middleware(['auth', 'role:LECTURER'])->prefix('lecturer')->name('lecturer.')->group(function () {
    Route::get('/dashboard', [LecturerDashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [App\Http\Controllers\Lecturer\Profile::class, 'index'])->name('profile');
    Route::get('/classes', [LecturerController::class, 'classes'])->name('classes');
    Route::get('/classes/{class}', function () { return 'Class details'; })->name('classes.show');
    Route::get('/assignments', function () { return 'Assignments list'; })->name('assignments');
    Route::get('/announcements', function () { return 'Announcements'; })->name('announcements');
});

// Student Routes
Route::middleware(['auth', 'role:STUDENT'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [App\Http\Controllers\Student\Profile::class, 'index'])->name('profile');
    Route::get('/study', function () { return view('student.studentStudy'); })->name('study');
    Route::get('/history', [studentHistoryController::class, 'history'])->name('history');
    Route::get('/classes/{class}', function () { return 'Class details'; })->name('classes.show');
});


