<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PacksAttendance;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    use PacksAttendance;


public function create()
{
    $date = today()->toDateString(); // ← 'YYYY-MM-DD'
    $state = 'not_working';

    $attendance = Attendance::with('breakTimes')
        ->where('user_id', Auth::id())
        ->whereDate('work_date', $date)
        ->latest('id')
        ->first();

    if ($attendance) {
        if (!is_null($attendance->clock_out) || $attendance->status === 'closed') {
            $state = 'closed';               // 退勤済み ボタンなし
        } elseif (is_null($attendance->clock_in)) {
            $state = 'not_working';           // 出勤前 出勤ボタンのみ
        } elseif ($attendance->breakTimes()->whereNull('end')->exists()) {
            $state = 'on_break';              // 休憩中 休憩終了＋退勤ボタン
        } else {
            $state = 'working';               // 出勤中 休憩開始＋退勤ボタン
        }
    }

    $badgeTextMap = [
        'not_working' => '勤務外',
        'working'     => '出勤中',
        'on_break'    => '休憩中',
        'closed'      => '退勤済',
    ];

    $badge = [
        'text'  => $badgeTextMap[$state] ?? '未設定',
        'class' => 'badge',
    ];

    $dateY  = Carbon::parse($date)->isoFormat('YYYY年');
    $dateMD = Carbon::parse($date)->isoFormat('M月D日 (ddd)');

    return view('attendance.create', compact('attendance', 'date', 'dateY', 'dateMD', 'state', 'badge'));
}

    public function clockIn()
{
    $date = today()->toDateString();

    $attendance = Attendance::firstOrCreate(
        [
            'user_id'   => Auth::id(),
            'work_date' => $date,
        ],
        [
            'status' => 'working',
        ]
    );

    if (!is_null($attendance->clock_in)) {
        return back();
    }

    $attendance->update([
        'clock_in' => now(),
        'status'   => 'working',
    ]);

    return back();
}

    public function breakIn()
    {
        $date = today()->toDateString();

        $attendance = Attendance::where('user_id', Auth::id())
            ->whereDate('work_date', $date)
            ->first();

        if (!$attendance) {
            return back();
        }
        if ($attendance->clock_out) {
            return back();
        }
        if ($attendance->breakTimes()->whereNull('end')->exists()) {
            return back();
        }

        $attendance->breakTimes()->create(['start' => now()]);

        return back();
    }

    public function breakOut()
    {
        $date = today()->toDateString();

        $attendance = Attendance::where('user_id', Auth::id())
            ->where('work_date', $date)
            ->first();

        if (!$attendance) {
            return back();
        }
        if ($attendance->clock_out) {
            return back();
        }

        $open = $attendance->breakTimes()->whereNull('end')->first();
        if (!$open) {
            return back();
        }

        $open->update(['end' => now()]);
            return back();
    }

    public function clockOut()
    {
        $date = today()->toDateString();

        $attendance = Attendance::where('user_id', Auth::id())
            ->where('work_date', $date)
            ->first();

        if (!$attendance) {
            return back();
        }
        if ($attendance->clock_out) {
            return back();
        }

        if ($attendance->breakTimes()->whereNull('end')->exists()) {
            return back();
        }

        $attendance->update([
            'clock_out' => now(),
            'status'    => 'closed',
        ]);

        return back()->with('status', 'お疲れさまでした。');
    }

    public function indexMonth(string $month)
{
    // 月初・月末の日付文字列を取得
    $base      = Carbon::createFromFormat('Y-m', $month, config('app.timezone'));
    $startDate = $base->copy()->startOfMonth()->toDateString();
    $endDate   = $base->copy()->endOfMonth()->toDateString();

    // タイトル・前月・次月
    $titleYM   = $base->isoFormat('YYYY/MM');
    $prevMonth = $base->copy()->subMonth()->format('Y-m');
    $nextMonth = $base->copy()->addMonth()->format('Y-m');

    // 当該月の勤怠データをまとめて取得
    $rows = Attendance::with('breakTimes')
        ->where('user_id', auth()->id())
        ->whereBetween('work_date', [$startDate, $endDate])
        ->orderBy('work_date')
        ->get();

    $byDate = $rows->keyBy(fn (Attendance $a) => $a->work_date->toDateString());

    // 日付ループで表示用データを組み立て
    $list = [];
    for ($d = Carbon::parse($startDate); $d->lte(Carbon::parse($endDate)); $d->addDay()) {
        $date = $d->toDateString();
        /** @var Attendance|null $att */
        $att  = $byDate->get($date);

        // 出退勤（cast により Carbon|null なので ?->format だけでいい）
        $clockIn  = $att?->clock_in?->format('H:i')  ?? '';
        $clockOut = $att?->clock_out?->format('H:i') ?? '';

        // アクセサ（getBreakMinutesAttribute / getWorkMinutesAttribute）
        $breakMin = $att?->break_minutes;
        $workMin  = $att?->work_minutes;

        $list[] = [
            'work_date' => $date,
            'clock_in'  => $clockIn,
            'clock_out' => $clockOut,
            'break_min' => $breakMin,
            'break_hm'  => $this->toHM($breakMin), // 表示用 "H:MM" or "—"
            'work_min'  => $workMin,
            'work_hm'   => $this->toHM($workMin),  // 表示用
            'detail_url'=> route('attendance.detail', ['date' => $date]),
        ];
    }

    return view('attendance.list', compact('titleYM', 'prevMonth', 'nextMonth', 'list'));
}

private function toHM(?int $minutes): string
{
    if ($minutes === null) return '—';
    $h = intdiv($minutes, 60);
    $m = $minutes % 60;
    return sprintf('%d:%02d', $h, $m);
}
    public function today()
    {
        $attendance = Attendance::with('breakTimes')
            ->where('user_id', Auth::id())
            ->forToday()
            ->latest('id')
            ->first();

        $date = now(config('app.timezone'))->toDateString();
        $dateY  = Carbon::parse($date)->isoFormat('YYYY年');
        $dateMD = Carbon::parse($date)->isoFormat('M月D日');

        return view('attendance.detail', [
            'attendance' => $attendance,
            'date'   => $date,
            'dateY'  => $dateY,
            'dateMD' => $dateMD,
        ]);
    }

    public function show(string $date)
{
    $user    = auth()->user();
    $isAdmin = $user->isAdmin();
    $targetUserId = $isAdmin ? (int)request('user_id', $user->id) : $user->id;


    // 当日の勤怠
    $attendance = Attendance::with('breakTimes')
        ->where('user_id', $targetUserId)
        ->whereDate('work_date', $date)
        ->first();

    $attendanceId = optional($attendance)->id;

    // 当日の最新の申請
    $latestRequest = StampCorrectionRequest::query()
        ->where('user_id', $targetUserId)
        ->where(function ($q) use ($attendanceId, $date) {
            if ($attendanceId) {
            $q->where('attendance_id', $attendanceId)
                ->orWhere(function ($qq) use ($date) {
                $qq->whereNull('attendance_id')
                    ->where(function ($qqq) use ($date) {
                    $qqq->whereDate('requested_clock_in',  $date)
                            ->orWhereDate('requested_clock_out', $date);
                        });
                    });
            } else {
                $q->where(function ($qq) use ($date) {
                    $qq->whereDate('requested_clock_in',  $date)
                        ->orWhereDate('requested_clock_out', $date);
                });
            }
        })
        ->latest('id')
        ->first();

    // 画面表示用の状態判定
    $status  = 'editable';
    $footer  = $isAdmin ? 'admin_update' : 'request';
    $canEdit = $isAdmin;

    if ($latestRequest) {
        if ($latestRequest->status === 'pending') {
            $status  = 'pending';
            $footer  = $isAdmin ? 'approve' : 'message'; // 管理者=承認 / スタッフ=赤メッセージ
            $canEdit = $isAdmin ? true : false;          // スタッフは編集不可
        } elseif ($latestRequest->status === 'approved') {
            $status  = 'approved';
            $footer  = 'approved';
            $canEdit = false;
        }
    }

$reqForOverlay = ($latestRequest && $latestRequest->status === 'pending') ? $latestRequest : null;

$attForView = $this->overlayAttendanceWithRequest($attendance, $reqForOverlay, $targetUserId, $date);

    $view = $this->packDetail($attForView, $user, $date, [
        'role'    => $isAdmin ? 'admin' : 'staff',
        'status'  => $status,
        'canEdit' => $canEdit,
        'footer'  => $footer,
        'form'    => match ($footer) {
            'approve'      => ['action' => route('admin.requests.approve'), 'method' => 'post'],
            'admin_update' => ['action' => route('admin.attendances.update', ['date' => $date, 'id' => $targetUserId]), 'method' => 'post'],
            'request'      => ['action' => route('requests.store'), 'method' => 'post'],
            default        => null,
        },
    ]);

    $view['title']  = Carbon::parse($date)->isoFormat('YYYY/MM/DD (dd)');
    $view['dateY']  = Carbon::parse($date)->isoFormat('YYYY年');
    $view['dateMD'] = Carbon::parse($date)->isoFormat('M月D日');

    return view('attendance.detail', $view);
}
}