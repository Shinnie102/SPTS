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
 //route get api admin
    Route::get('/dashboard/api/data', [AdminDashboardController::class, 'getDashboardData'])
    ->name('dashboard.api.data');
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
    Route::get('/hoc-thuat', [\App\Http\Controllers\Admin\AcademicStructureController::class, 'index'])->name('hocthuat');

    // Faculty APIs
    Route::prefix('hoc-thuat/faculty')->name('hocthuat.faculty.')->group(function () {
        Route::get('/api', [\App\Http\Controllers\Admin\AcademicStructureController::class, 'getFaculties'])->name('api.index');
        Route::get('/api/active', [\App\Http\Controllers\Admin\AcademicStructureController::class, 'getActiveFaculties'])->name('api.active');
        Route::post('/api', [\App\Http\Controllers\Admin\AcademicStructureController::class, 'storeFaculty'])->name('api.store');
        Route::get('/api/{facultyId}', [\App\Http\Controllers\Admin\AcademicStructureController::class, 'showFaculty'])->name('api.show');
        Route::put('/api/{facultyId}', [\App\Http\Controllers\Admin\AcademicStructureController::class, 'updateFaculty'])->name('api.update');
        Route::delete('/api/{facultyId}', [\App\Http\Controllers\Admin\AcademicStructureController::class, 'deleteFaculty'])->name('api.delete');
        Route::patch('/api/{facultyId}/toggle-status', [\App\Http\Controllers\Admin\AcademicStructureController::class, 'toggleFacultyStatus'])->name('api.toggleStatus');
    });

    // Major APIs
    Route::prefix('hoc-thuat/major')->name('hocthuat.major.')->group(function () {
        Route::get('/api/active', [\App\Http\Controllers\Admin\AcademicStructureController::class, 'getActiveMajors'])->name('api.active');
        Route::get('/api/by-faculty/{facultyId}', [\App\Http\Controllers\Admin\AcademicStructureController::class, 'getMajorsByFaculty'])->name('api.byFaculty');
        Route::post('/api', [\App\Http\Controllers\Admin\AcademicStructureController::class, 'storeMajor'])->name('api.store');
        Route::get('/api/{majorId}', [\App\Http\Controllers\Admin\AcademicStructureController::class, 'showMajor'])->name('api.show');
        Route::put('/api/{majorId}', [\App\Http\Controllers\Admin\AcademicStructureController::class, 'updateMajor'])->name('api.update');
        Route::delete('/api/{majorId}', [\App\Http\Controllers\Admin\AcademicStructureController::class, 'deleteMajor'])->name('api.delete');
    });

    // Course APIs
    Route::prefix('hoc-thuat/course')->name('hocthuat.course.')->group(function () {
        Route::get('/api', [\App\Http\Controllers\Admin\AcademicStructureController::class, 'getCourses'])->name('api.index');
        Route::post('/api', [\App\Http\Controllers\Admin\AcademicStructureController::class, 'storeCourse'])->name('api.store');
        Route::get('/api/{courseId}', [\App\Http\Controllers\Admin\AcademicStructureController::class, 'showCourse'])->name('api.show');
        Route::put('/api/{courseId}', [\App\Http\Controllers\Admin\AcademicStructureController::class, 'updateCourse'])->name('api.update');
        Route::delete('/api/{courseId}', [\App\Http\Controllers\Admin\AcademicStructureController::class, 'deleteCourse'])->name('api.delete');
        Route::patch('/api/{courseId}/toggle-lock', [\App\Http\Controllers\Admin\AcademicStructureController::class, 'toggleCourseLock'])->name('api.toggleLock');
    });

    // Helper APIs
    Route::get('/hoc-thuat/grading-schemes/api/active', [\App\Http\Controllers\Admin\AcademicStructureController::class, 'getActiveGradingSchemes'])->name('hocthuat.gradingSchemes.api.active');
    Route::get('/hoc-thuat/course/api/check-code/{code}', [\App\Http\Controllers\Admin\AcademicStructureController::class, 'checkCourseCode'])->name('hocthuat.course.api.checkCode');

    // Quy tắc đánh giá
    Route::get('/quy-tac', [\App\Http\Controllers\Admin\GradingSchemeController::class, 'index'])->name('quytac');
    
    Route::prefix('quy-tac')->name('quytac.')->group(function () {
        // API: Lấy tất cả dữ liệu (quy tắc + sơ đồ điểm)
        Route::get('/api/data', [\App\Http\Controllers\Admin\GradingSchemeController::class, 'getData'])->name('api.data');
        
        // API: Quy tắc học vụ
        Route::get('/api/academic-rules', [\App\Http\Controllers\Admin\GradingSchemeController::class, 'getAcademicRules'])->name('api.academicRules');
        
        // API: Sơ đồ điểm
        Route::get('/api/grading-schemes', [\App\Http\Controllers\Admin\GradingSchemeController::class, 'getGradingSchemes'])->name('api.gradingSchemes');
        Route::post('/api/grading-schemes', [\App\Http\Controllers\Admin\GradingSchemeController::class, 'store'])->name('api.store');
        Route::get('/api/grading-schemes/{id}', [\App\Http\Controllers\Admin\GradingSchemeController::class, 'show'])->name('api.show');
        Route::put('/api/grading-schemes/{id}', [\App\Http\Controllers\Admin\GradingSchemeController::class, 'update'])->name('api.update');
        Route::delete('/api/grading-schemes/{id}', [\App\Http\Controllers\Admin\GradingSchemeController::class, 'destroy'])->name('api.destroy');
    });

    // Thời gian học vụ
    Route::prefix('thoi-gian')->name('thoigian.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AcademicTimeController::class, 'index'])->name('index');

        // Academic Year APIs
        Route::get('/api/academic-years', [\App\Http\Controllers\Admin\AcademicTimeController::class, 'getAcademicYears'])->name('api.academicYears');
        Route::post('/api/academic-years', [\App\Http\Controllers\Admin\AcademicTimeController::class, 'storeAcademicYear'])->name('api.storeAcademicYear');
        Route::delete('/api/academic-years/{academicYearId}', [\App\Http\Controllers\Admin\AcademicTimeController::class, 'deleteAcademicYear'])->name('api.deleteAcademicYear');

        // Semester APIs
        Route::get('/api/semesters/{semesterId}', [\App\Http\Controllers\Admin\AcademicTimeController::class, 'showSemester'])->name('api.showSemester');
        Route::post('/api/semesters', [\App\Http\Controllers\Admin\AcademicTimeController::class, 'storeSemester'])->name('api.storeSemester');
        Route::put('/api/semesters/{semesterId}', [\App\Http\Controllers\Admin\AcademicTimeController::class, 'updateSemester'])->name('api.updateSemester');
        Route::delete('/api/semesters/{semesterId}', [\App\Http\Controllers\Admin\AcademicTimeController::class, 'deleteSemester'])->name('api.deleteSemester');
    });
});

// Lecturer Routes
Route::middleware(['auth', 'role:LECTURER'])->prefix('lecturer')->name('lecturer.')->group(function () {
    Route::get('/dashboard', [LecturerDashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [App\Http\Controllers\Lecturer\Profile::class, 'index'])->name('profile');
    Route::post('/profile/update', [App\Http\Controllers\Lecturer\Profile::class, 'update'])->name('profile.update');

    // Lớp học phần - Sử dụng Controller mới
    Route::get('/classes', [ClassController::class, 'index'])->name('classes');
    Route::get('/class/{id}', [ClassController::class, 'show'])->name('class.detail');
    
    // Các route cho từng chức năng của lớp học phần
    Route::get('/class/{id}/attendance', [AttendanceController::class, 'attendance'])->name('attendance');
    Route::get('/class/{id}/attendance-data/{meetingId}', [AttendanceController::class, 'getAttendanceData'])->name('attendance.data');
    Route::post('/class/{id}/attendance/save', [AttendanceController::class, 'saveAttendance'])->name('attendance.save');
    Route::get('/class/{id}/grading', [GradingController::class, 'grading'])->name('grading');
    Route::get('/class/{id}/grading-data', [GradingController::class, 'getGradingData'])->name('grading.data');
    Route::post('/class/{id}/grading/save', [GradingController::class, 'saveGrading'])->name('grading.save');
    Route::post('/class/{id}/grading/lock', [GradingController::class, 'lockGrades'])->name('grading.lock');
    Route::get('/class/{id}/status', [ClassController::class, 'status'])->name('class.status');
    Route::get('/class/{id}/export-scores', [ClassController::class, 'exportScores'])->name('class.exportScores');
    Route::get('/class/{id}/report', [ReportController::class, 'report'])->name('report');
    Route::get('/class/{id}/report-data', [ReportController::class, 'getReportData'])->name('report.data');
    Route::get('/class/{id}/report-export', [ReportController::class, 'exportReport'])->name('report.export');

    // API: Student detail in a class (for Report modal)
    Route::get('/class/{classId}/student/{studentId}/detail', [ReportController::class, 'getStudentDetail'])->name('report.student.detail');
});

// Student Routes
Route::middleware(['auth', 'role:STUDENT'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [App\Http\Controllers\Student\Profile::class, 'index'])->name('profile');
    Route::get('/study', [StudentStudyController::class, 'index'])->name('study');
    Route::get('/history', [studentHistoryController::class, 'history'])->name('history');
    Route::get('/classes/{class}', function () {
        return 'Class details';
    })->name('classes.show');

});
