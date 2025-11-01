<?php

use App\Http\Controllers\Admin\AdminAttendanceController;  // 管理者：日次（全員）
use App\Http\Controllers\Admin\AdminAuthController;        // 管理者ログイン/ログアウト
use App\Http\Controllers\Admin\AdminRequestController;     // 管理者：申請一覧・承認
use App\Http\Controllers\Admin\AdminUserController;        // 管理者：スタッフ一覧・スタッフ別月次

// スタッフ側
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\StampCorrectionRequestController;

// 管理者側
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// ======================================================
// 公開トップ：ログイン済みなら勤怠TOP、未ログインならログインへ
// ======================================================
Route::get('/', function () {
    return auth()->check() ? redirect()->route('attendance.detail')
        : redirect()->route('login');
})->name('home');

// ======================================================
// メール認証（共通）：要ログイン
// ======================================================
Route::middleware('auth')->group(function () {

    // 認証待ち画面
    Route::get('/email/verify', fn() => view('auth.verify'))
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

    // 打刻（今日の状態画面 + ボタン）
    Route::get('/attendance', [AttendanceController::class, 'create'])
        ->name('attendance.create');

    // 打刻POST
    Route::post('/attendance/clock-in',  [AttendanceController::class, 'clockIn'])->name('attendance.clock-in');
    Route::post('/attendance/break-in',  [AttendanceController::class, 'breakIn'])->name('attendance.break-in');
    Route::post('/attendance/break-out', [AttendanceController::class, 'breakOut'])->name('attendance.break-out');
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clock-out');

    // 勤怠詳細（編集可）
    Route::get('/attendance/{date}', [AttendanceController::class, 'show'])
        ->where('date', '\d{4}-\d{2}-\d{2}')
        ->name('attendance.detail');

    // 月次
    Route::get('/attendance/month/{month}', [AttendanceController::class, 'indexMonth'])
        ->where('month', '\d{4}-\d{2}')
        ->name('attendance.list');

    // 申請の保存
    Route::post('/requests/attendance', [StampCorrectionRequestController::class, 'store'])
        ->name('requests.store');

    // 申請一覧
    Route::get('/requests', [StampCorrectionRequestController::class, 'index'])
        ->name('requests.list');

    // ======================================================
    // 共通ログアウト（スタッフ側） POST
    // ======================================================
    Route::post('/logout', function () {
        Auth::guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/login');
    })->name('logout');
});

// ======================================================
// 管理者：ログイン/ログアウト
// ======================================================
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login',  [AdminAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.post');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
});

// ======================================================
// 管理者：保護された画面（要ログイン & 認証済み & admin-only）
// ======================================================
Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified', 'can:admin-only'])->group(function () {

    // 当日リダイレクト
    Route::get('/attendances', function () {
        return redirect()->route('admin.attendances.index', ['date' => today()->toDateString()]);
    })->name('attendances.today');

    // 勤怠 日次一覧・詳細・更新（管理）
    Route::get('/attendances/{date}', [AdminAttendanceController::class, 'index'])
        ->where('date', '\d{4}-\d{2}-\d{2}')
        ->name('attendances.index');
    Route::get('/attendances/{date}/users/{id}', [AdminAttendanceController::class, 'show'])
        ->whereNumber('id')->where('date', '\d{4}-\d{2}-\d{2}')
        ->name('attendances.show');
    Route::post('/attendances/{date}/users/{id}', [AdminAttendanceController::class, 'update'])
        ->whereNumber('id')->where('date', '\d{4}-\d{2}-\d{2}')
        ->name('attendances.update');
    // 申請詳細・承認
    // 同じURLで一覧/詳細を切り替える
    Route::get('/requests', [AdminRequestController::class, 'index'])
        ->name('requests.index');
    // 承認（POSTして、同じURLに戻すだけ）
    Route::post('/requests/approve', [AdminRequestController::class, 'approve'])->name('requests.approve');

    // スタッフ一覧
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');

    // スタッフ別の月次一覧
    Route::get('/users/{id}/attendances/{month}', [AdminUserController::class, 'attendances'])
        ->where('id', '\d+')->where('month', '\d{4}-\d{2}')
        ->name('users.attendances');
    // csv出力
    Route::get('/users/{id}/attendances/{month}/csv', [AdminUserController::class, 'exportMonth'])
        ->where('id', '\d+')->where('month', '\d{4}-\d{2}')
        ->name('users.attendances.csv');
});
