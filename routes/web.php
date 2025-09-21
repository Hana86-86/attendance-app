<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Admin\AdminAuthController;

Route::get('/', function () {
    return view('welcome');
});


// ユーザー用ルート
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/attendance', [AttendanceController::class,'create'])->name('attendance.create');
    Route::post('/attendance/clock-in', [AttendanceController::class,'clockIn'])->name('attendance.clock-in');
    Route::post('/attendance/clock-out', [AttendanceController::class,'clockOut'])->name('attendance.clock-out');
    Route::post('/attendance/break-in', [AttendanceController::class,'breakIn'])->name('attendance.break-in');
    Route::post('/attendance/break-out', [AttendanceController::class,'breakOut'])->name('attendance.break-out');
    Route::get('/attendance/list', [AttendanceController::class,'index'])->name('attendance.list');
    Route::get('/attendance/detail/{id}', [AttendanceController::class,'show'])->name('attendance.detail');
    //申請(一般）
    Route::get('/stamp_correction_request/list', [StampCorrectionRequestController::class,'index']);
    Route::get('/stamp_correction_request', [StampCorrectionRequestController::class,'store']);
});
// 管理者用ルート
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.post');

    Route::middleware(['auth', 'can:admin-only'])->group(function () {
        Route::get('attendances', [AdminAttendanceController::class, 'index'])->name('attendances.index');
        Route::get('attendances/{id}', [AdminAttendanceController::class, 'show'])->name('attendances.show');
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}/attendances', [AdminUserController::class, 'attendances'])->name('users.attendances');
        Route::get('/requests', [AdminRequestController::class, 'index'])->name('requests.index');
        Route::get('/requests/{id}', [AdminRequestController::class, 'show'])->name('requests.show');
    });
});
