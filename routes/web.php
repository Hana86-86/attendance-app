<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

// スタッフ側
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\StampCorrectionRequestController;

// 管理者側
use App\Http\Controllers\Admin\AdminAuthController;        // 管理者ログイン/ログアウト
use App\Http\Controllers\Admin\AdminAttendanceController;  // 管理者：日次（全員）
use App\Http\Controllers\Admin\AdminUserController;        // 管理者：スタッフ一覧・スタッフ別月次
use App\Http\Controllers\Admin\AdminRequestController;     // 管理者：申請一覧・承認

// ======================================================
// 公開トップ：ログイン済みなら勤怠TOP、未ログインならログインへ
// ======================================================
Route::get('/', function () {
    return Auth::check()
        ? redirect('/attendance')
        : redirect()->route('login');
})->name('home');

// ======================================================
// メール認証（共通）：要ログイン
// ======================================================
Route::middleware('auth')->group(function () {

    // 認証待ち画面
    Route::get('/email/verify', fn () => view('auth.verify'))
        ->name('verification.notice');

    // 認証リンクの検証
    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        return redirect('/attendance');
    })->middleware(['signed'])->name('verification.verify');

    // 認証メール再送
    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('status', 'verification-link-sent');
    })->middleware(['throttle:6,1'])->name('verification.send');
});

// ======================================================
// スタッフ（一般ユーザー）ルート：要ログイン & メール認証
// ======================================================
Route::middleware(['auth', 'verified'])->group(function () {

    // 打刻画面 / 打刻API
    Route::get('/attendance',                [AttendanceController::class,'create'])->name('attendance.create');
    Route::post('/attendance/clock-in',      [AttendanceController::class,'clockIn'])->name('attendance.clock-in');
    Route::post('/attendance/clock-out',     [AttendanceController::class,'clockOut'])->name('attendance.clock-out');
    Route::post('/attendance/break-in',      [AttendanceController::class,'breakIn'])->name('attendance.break-in');
    Route::post('/attendance/break-out',     [AttendanceController::class,'breakOut'])->name('attendance.break-out');

    // 勤怠詳細（当日1件）
    Route::get('/attendance/{date}/detail', [AttendanceController::class, 'show'])
        ->where('date','\d{4}-\d{2}-\d{2}')
        ->name('attendance.detail');

    // 勤怠一覧（月）
    Route::get('/attendance/{month}', [AttendanceController::class, 'indexMonth'])
        ->name('attendance.list');

    // 修正申請（詳細画面からPOST）
    Route::post('/requests/{date}', [StampCorrectionRequestController::class,'store'])
        ->where('date','\d{4}-\d{2}-\d{2}')
        ->name('requests.store');

    // 修正申請一覧
    Route::get('/requests/{status?}', [StampCorrectionRequestController::class, 'index'])
        ->whereIn('status', ['pending','approved'])
        ->name('requests.list');

    // 申請詳細
    Route::get('/requests/{id}/show', [StampCorrectionRequestController::class, 'show'])
        ->whereNumber('id')
        ->name('requests.show');
});

// ======================================================
// 共通ログアウト（スタッフ側） POST
// ======================================================
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/login');
})->name('logout');

// ======================================================
// 管理者：ログイン/ログアウト
// ======================================================
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login',  [AdminAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.post');
    Route::post('/logout',[AdminAuthController::class, 'logout'])->name('logout');
});

// ======================================================
// 管理者：保護された画面（要ログイン & 認証済み & admin-only）
// ======================================================
Route::middleware(['auth', 'verified', 'can:admin-only'])
    ->prefix('admin')->name('admin.')->group(function () {

    // 今日へリダイレクト（ブックマーク用のショートカット）
    Route::get('/attendances', fn () =>
        redirect()->route('admin.attendances.index', ['date' => now()->toDateString()])
    )->name('attendances.today');

    // 日次（全ユーザー） 例: /admin/attendances/2025-09-30
    Route::get('/attendances/{date}', [AdminAttendanceController::class, 'index'])
        ->where('date','\d{4}-\d{2}-\d{2}')
        ->name('attendances.index');

    // 日次詳細
    Route::get('/attendances/{date}/users/{id}', [AdminAttendanceController::class, 'show'])
        ->where('date','\d{4}-\d{2}-\d{2}')->where('id','\d+')
        ->name('attendances.show');

    // スタッフ一覧
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');

    // スタッフ別の月次一覧 例: /admin/users/12/attendances/2025-09
    Route::get('/users/{id}/attendances/{month}', [AdminUserController::class, 'attendances'])
        ->where('id','\d+')->where('month','\d{4}-\d{2}')
        ->name('users.attendances');

    // 申請（承認待ち／承認済み／詳細／承認）
    Route::get('/requests/pending',           [AdminRequestController::class, 'pending'])->name('requests.pending');
    Route::get('/requests/approved',          [AdminRequestController::class, 'approved'])->name('requests.approved');
    Route::get('/requests/{id}',              [AdminRequestController::class, 'show'])->where('id','\d+')->name('requests.show');
    Route::post('/requests/{id}/approve',     [AdminRequestController::class, 'approve'])->where('id','\d+')->name('requests.approve');
});