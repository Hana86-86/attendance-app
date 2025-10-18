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
            $state = 'closed';                // 退勤後 → メッセージのみ
        } elseif (is_null($attendance->clock_in)) {
            $state = 'not_working';           // 出勤打刻なし → 出勤ボタンのみ
        } elseif ($attendance->breakTimes()->whereNull('end')->exists()) {
            $state = 'on_break';              // 休憩中 → 休憩戻ボタンのみ
        } else {
            $state = 'working';               // 出勤済み・休憩開いてない → 退勤＋休憩入
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
    $base  = Carbon::createFromFormat('Y-m', $month, config('app.timezone'));

    $start = $base->copy()->startOfMonth()->toDateString();
    $end   = $base->copy()->endOfMonth()->toDateString();

    $titleYM   = $base->isoFormat('YYYY/MM');
    $prevMonth = $base->copy()->subMonth()->format('Y-m');
    $nextMonth = $base->copy()->addMonth()->format('Y-m');

    $rows = Attendance::with('breakTimes')
        ->where('user_id', Auth::id())
        ->whereBetween('work_date', [$start, $end])
        ->orderBy('work_date')
        ->get();

    $byDate = $rows->keyBy(function ($r) {
        $wd = $r->work_date;
        return $wd instanceof Carbon
            ? $wd->toDateString()
            : Carbon::parse($wd)->toDateString();
    });

    $list = [];
    for ($d = Carbon::parse($start); $d->lte(Carbon::parse($end)); $d->addDay()) {
        $date = $d->toDateString();
        $att  = $byDate->get($date);

        $ci = $att?->clock_in;
        $co = $att?->clock_out;

        $clockIn  = $ci ? ($ci instanceof Carbon ? $ci : Carbon::parse($ci))->format('H:i') : '';
        $clockOut = $co ? ($co instanceof Carbon ? $co : Carbon::parse($co))->format('H:i') : '';

        // 休憩合計（分）
        $breakMin = 0;
        if ($att) {
            foreach ($att->breakTimes as $bt) {
                if ($bt->start && $bt->end) {
                    $breakMin += Carbon::parse($bt->start)->diffInMinutes(Carbon::parse($bt->end));
                }
            }
        }
        $breakMin = $breakMin > 0 ? $breakMin : null;

        // 勤務合計（分）
        $workMin = null;
        if ($ci && $co) {
            $ciC = $ci instanceof Carbon ? $ci : Carbon::parse($ci);
            $coC = $co instanceof Carbon ? $co : Carbon::parse($co);
            $total   = $ciC->diffInMinutes($coC);
            $workMin = max(0, $total - (int)($breakMin ?? 0));
        }

        $list[] = [
            'clock_in'   => $clockIn,                 // 例: "08:00" or ""
            'clock_out'  => $clockOut,                // 例: "17:30" or ""
            'break_min'  => $breakMin,                // 例: 60 or null
            'break_hm'   => $this->toHM($breakMin),   // 例: "1:00"
            'work_min'   => $workMin,                 // 例: 450
            'work_hm'    => $this->toHM($workMin),    // 例: "7:30"
            'work_date'  => $date,                    // 例: "2025-10-04"
            'detail_url' => route('attendance.detail', ['date' => $date]),
        ];
    }

    return view('attendance.list', compact('titleYM','prevMonth','nextMonth','list'));
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
    $user = auth()->user();

    // 当日の勤怠
    $attendance = Attendance::with('breakTimes')
        ->where('user_id', $user->is_admin ? request('user_id', $user->id) : $user->id)
        ->whereDate('work_date', $date)
        ->first();

    // この日の「未承認の修正申請」を1件取得（existsではなく first で本体を持つ）
    $pendingReq = \App\Models\StampCorrectionRequest::query()
        ->when($attendance, fn($q) => $q->where('attendance_id', $attendance->id))
        ->when(!$attendance, function ($q) use ($date) {
            $q->whereDate('requested_clock_in',  $date)
              ->orWhereDate('requested_clock_out', $date);
        })
        ->where('status', 'pending')
        ->latest('created_at')
        ->first();

    $isPending = (bool) $pendingReq;
    $isAdmin   = request()->routeIs('admin.*') || ($user->is_admin ?? false);

    // ▼ ここで UI を決め切る（footer と form が要）
    if ($isAdmin) {
        $ui = [
            'role'    => 'admin',
            'status'  => $isPending ? 'pending' : 'approved',
            'canEdit' => false, // 管理者が直接修正する仕様にしたい場合は true に
            'footer'  => $isPending ? 'approve' : 'approved',
            'form'    => $isPending ? [
                'action' => route('admin.requests.approve'),
                'method' => 'post',
            ] : null,
        ];
    } else {
        $ui = [
            'role'    => 'staff',
            'status'  => $isPending ? 'pending' : 'editable',
            'canEdit' => !$isPending,                 // 承認待ちは修正不可
            'footer'  => $isPending ? 'message' : 'request',
            'form'    => !$isPending ? [              // ← ここ大事：スタッフ申請フォーム
                'action' => route('requests.store'),
                'method' => 'post',
            ] : null,
        ];
    }

    // 共通の表示値を構築（既存の Trait）
    $view = $this->packDetail($attendance, $user, $date, $ui);

    // 承認POST用 hidden
    $view['detailId'] = $pendingReq?->id;

    // 日付（表示用）
    $view['dateY']  = \Carbon\Carbon::parse($date)->isoFormat('YYYY年');
    $view['dateMD'] = \Carbon\Carbon::parse($date)->isoFormat('M月D日');

    return view('attendance.detail', $view);
}
}