<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Lecturer\DashboardController as LecturerDashboardController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;

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
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/users', function () { return view('admin.adminUsers'); })->name('users');
});

// Lecturer Routes
Route::middleware(['auth', 'role:lecturer'])->prefix('lecturer')->name('lecturer.')->group(function () {
    Route::get('/dashboard', [LecturerDashboardController::class, 'index'])->name('dashboard');
    Route::get('/classes', function () { return 'Classes list'; })->name('classes');
    Route::get('/classes/{class}', function () { return 'Class details'; })->name('classes.show');
    Route::get('/assignments', function () { return 'Assignments list'; })->name('assignments');
    Route::get('/announcements', function () { return 'Announcements'; })->name('announcements');
});

// Student Routes
Route::middleware(['auth', 'role:student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
    Route::get('/study', function () { return view('student.studentStudy'); })->name('study');
    Route::get('/history', function () { return view('student.studentHistory'); })->name('history');
    Route::get('/classes/{class}', function () { return 'Class details'; })->name('classes.show');
});


