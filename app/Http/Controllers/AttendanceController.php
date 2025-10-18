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
            $state = 'closed';               // 退勤済み → 何もボタンなし
        } elseif (is_null($attendance->clock_in)) {
            $state = 'not_working';           // 出勤前 → 出勤ボタンのみ
        } elseif ($attendance->breakTimes()->whereNull('end')->exists()) {
            $state = 'on_break';              // 休憩中 → 休憩終了＋退勤ボタン
        } else {
            $state = 'working';               // 出勤中 → 休憩開始＋退勤ボタン
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
            ->where('work_date', today()->toDateString())
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
            ->where('work_date', today()->toDateString())
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
    // ① 月初・月末の日付文字列を取得
    $base      = Carbon::createFromFormat('Y-m', $month, config('app.timezone'));
    $startDate = $base->copy()->startOfMonth()->toDateString(); // "YYYY-MM-01"
    $endDate   = $base->copy()->endOfMonth()->toDateString();   // "YYYY-MM-31"

    // ② タイトル・前月・次月
    $titleYM   = $base->isoFormat('YYYY/MM');
    $prevMonth = $base->copy()->subMonth()->format('Y-m');
    $nextMonth = $base->copy()->addMonth()->format('Y-m');

    // ③ 当該月の勤怠データをまとめて取得
    $rows = Attendance::with('breakTimes')
        ->where('user_id', auth()->id())
        ->whereBetween('work_date', [$startDate, $endDate])
        ->orderBy('work_date')
        ->get();

    $byDate = $rows->keyBy(fn (Attendance $a) => $a->work_date->toDateString());

    // ④ 日付ループで表示用データを組み立て
    $list = [];
    for ($d = Carbon::parse($startDate); $d->lte(Carbon::parse($endDate)); $d->addDay()) {
        $date = $d->toDateString();
        /** @var Attendance|null $att */
        $att  = $byDate->get($date);

        // 出退勤（cast により Carbon|null なので ?->format だけでOK）
        $clockIn  = $att?->clock_in?->format('H:i')  ?? '';
        $clockOut = $att?->clock_out?->format('H:i') ?? '';

        // アクセサ（getBreakMinutesAttribute / getWorkMinutesAttribute）
        $breakMin = $att?->break_minutes; // int|null
        $workMin  = $att?->work_minutes;  // int|null

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

/** 表示用：分 → "H:MM"（null は '—'） */
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
    $user     = auth()->user();
    $isAdmin  = request()->routeIs('admin.*') || ($user->is_admin ?? false);

    // 表示対象ユーザーID（スタッフ=本人、管理者=クエリ指定があればそれ）
    $targetUserId = $isAdmin ? (int)request('user_id', $user->id) : $user->id;

    // 当日の勤怠データ
    $attendance = Attendance::with('breakTimes')
        ->where('user_id', $targetUserId)
        ->whereDate('work_date', $date)
        ->first();

    /**
     * 承認待ち申請がそのユーザー・その日のものに限って存在するか？
     * - 当日の勤怠があれば attendance_id で絞る
     * - まだ勤怠が無いケースは“申請日＝当日”で clock_in / clock_out / 休憩のいずれかに該当するもの
     */
    $pendingReq = \App\Models\StampCorrectionRequest::query()
        ->where('user_id', $targetUserId)
        ->where('status', 'pending')
        ->when($attendance, function ($q) use ($attendance) {
            // 勤怠があるなら attendance_id で一意に
            $q->where('attendance_id', $attendance->id);
        }, function ($q) use ($date) {
            // 勤怠が未作成でも、当日分の申請は pending 扱いにする
            $q->where(function ($qq) use ($date) {
                $qq->whereDate('requested_clock_in',  $date)
                    ->orWhereDate('requested_clock_out', $date)
                    ->orWhereDate('requested_break_start', $date)
                    ->orWhereDate('requested_break_end',   $date);
            });
        })
        ->latest('created_at')
        ->first();

    $isPending = (bool) $pendingReq;
    // 編集可否フラグ
    // - スタッフ：pending 中は編集不可
    // - 管理者：pending でも直接修正可能
    $canEdit = $isAdmin ? true : !$isPending;

    // 右下フッターのボタン（カード共通パーシャル用）
    $footer = match (true) {
        $isAdmin && $isPending  => 'approve',      // 管理者：承認ボタン
        $isAdmin                => 'admin_update', // 管理者：直接修正
        !$isAdmin && $isPending => 'message',      // スタッフ：承認待ちで編集不可（赤文言）
        default                 => 'request',      // スタッフ：修正申請ボタン
    };

    $view = $this->packDetail($attendance, $user, $date, [
        'role'    => $isAdmin ? 'admin' : 'staff',
        'status'  => $isPending ? 'pending' : 'editable',
        'canEdit' => $canEdit,
        'footer'  => $footer,
        'form'    => match ($footer) {
            'approve'      => ['action' => route('admin.requests.approve'), 'method' => 'post'],
            'admin_update' => ['action' => route('admin.attendances.update', ['date' => $date, 'id' => $targetUserId]), 'method' => 'post'],
            'request'      => ['action' => route('requests.store'), 'method' => 'post'],
            default        => null,
        },
    ]);

    $view['title']  = \Carbon\Carbon::parse($date)->isoFormat('YYYY/MM/DD (dd)');
    $view['dateY']  = \Carbon\Carbon::parse($date)->isoFormat('YYYY年');
    $view['dateMD'] = \Carbon\Carbon::parse($date)->isoFormat('M月D日');

    return view('attendance.detail', $view);
}
}