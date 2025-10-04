<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\Attendance\{ClockInRequest, ClockOutRequest, BreakInRequest, BreakOutRequest};
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use App\Http\Controllers\Concerns\PacksAttendance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Collection;

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
        'not_working' => '未出勤',
        'working'     => '勤務中',
        'on_break'    => '休憩中',
        'closed'      => '退勤済',
    ];

    $badge = [
        'text'  => $badgeTextMap[$state] ?? '未設定',
        'class' => 'badge',
    ];

    $dateY  = \Carbon\Carbon::parse($date)->isoFormat('YYYY年');
    $dateMD = \Carbon\Carbon::parse($date)->isoFormat('M月D日 (ddd)');

    return view('attendance.create', compact('attendance', 'date', 'dateY', 'dateMD', 'state', 'badge'));
}

    public function clockIn(ClockInRequest $request)
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
        return back()->withErrors(['clock_in' => '本日はすでに出勤済みです。']);
    }

    $attendance->update([
        'clock_in' => now(),
        'status'   => 'working',
    ]);

    return back()->with('status', '出勤しました');
}

    public function breakIn(BreakInRequest $request)
    {
        $date = today()->toDateString();

        $attendance = Attendance::where('user_id', Auth::id())
            ->whereDate('work_date', $date)
            ->first();

        if (!$attendance) {
            return back()->withErrors(['break_in' => '本日はまだ出勤していません。']);
        }
        if ($attendance->clock_out) {
            return back()->withErrors(['break_in' => '本日はすでに退勤済みです。']);
        }
        if ($attendance->breakTimes()->whereNull('end')->exists()) {
            return back()->withErrors(['break_in' => 'すでに休憩中です。']);
        }

        $attendance->breakTimes()->create(['start' => now()]);

        return back()->with('status', '休憩に入りました');
    }

    public function breakOut(BreakOutRequest $request)
    {
        $date = today()->toDateString();

        $attendance = Attendance::where('user_id', Auth::id())
            ->where('work_date', today()->toDateString())
            ->first();

        if (!$attendance) {
            return back()->withErrors(['break_out' => '本日はまだ出勤していません。']);
        }
        if ($attendance->clock_out) {
            return back()->withErrors(['break_out' => '本日はすでに退勤済みです。']);
        }

        $open = $attendance->breakTimes()->whereNull('end')->first();
        if (!$open) {
            return back()->withErrors(['break_out' => '休憩中ではありません。']);
        }

        $open->update(['end' => now()]);

        return back()->with('status', '休憩から戻りました');
    }

    public function clockOut(ClockOutRequest $request)
    {
        $date = today()->toDateString();
        
        $attendance = Attendance::where('user_id', Auth::id())
            ->where('work_date', today()->toDateString())
            ->first();

        if (!$attendance) {
            return back()->withErrors(['clock_out' => '本日はまだ出勤していません。']);
        }
        if ($attendance->clock_out) {
            return back()->withErrors(['clock_out' => '本日はすでに退勤済みです。']);
        }

        if ($attendance->breakTimes()->whereNull('end')->exists()) {
            return back()->withErrors(['clock_out' => '休憩中のため退勤できません。先に「休憩戻」を実行してください。']);
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

        $titleYM  = $base->isoFormat('YYYY/MM');
        $prevMonth = $base->copy()->subMonth()->format('Y-m');
        $nextMonth = $base->copy()->addMonth()->format('Y-m');

        $rows = Attendance::with('breakTimes')
        ->where('user_id', Auth::id())
        ->whereBetween('work_date', [$start, $end])
        ->orderBy('work_date')
        ->get();


        $byDate = $rows->keyBy(fn ($r) => $r->work_date->toDateString());

    $list = [];
    for ($d = Carbon::parse($start); $d->lte($end); $d->addDay()) {
        $date = $d->toDateString();
        $att  = $byDate->get($date);

        $clockIn  = $att?->clock_in?->format('H:i')  ?? '';
        $clockOut = $att?->clock_out?->format('H:i') ?? '';

        // 休憩合計（分）
        $breakMin = 0;
        if ($att) {
            foreach ($att->breakTimes as $bt) {
                if ($bt->start && $bt->end) {
                    $breakMin += \Carbon\Carbon::parse($bt->start)->diffInMinutes(\Carbon\Carbon::parse($bt->end));
                }
            }
        }
        $breakMin = $breakMin > 0 ? $breakMin : null;
        // 勤務合計（分)
        $workMin = null;
        if ($att?->clock_in && $att?->clock_out) {
            $total   = $att->clock_in->diffInMinutes($att->clock_out);
            $workMin = max(0, $total - (int)($breakMin ?? 0)); // ← 数値だけ引く
        }

    $list[] = [
            'clock_in'  => $clockIn,
            'clock_out' => $clockOut,
            'break_min' => $breakMin,
            'break_hm'  => $this->toHM($breakMin),
            'work_min'  => $workMin,
            'work_hm'   => $this->toHM($workMin),
            'work_date' => $date,
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

    $attendance = Attendance::with('breakTimes')
        ->where('user_id', $user->id)
        ->whereDate('work_date', $date)
        ->first();

    // 当日の pending 申請があれば編集不可
    $isPending = StampCorrectionRequest::query()
        ->where('user_id', $user->id)
        ->when($attendance, fn($q) => $q->where('attendance_id', $attendance->id))
        ->where('status', 'pending')
        ->exists();

    $ui = [
        'role'     => 'staff',
        'status'   => $isPending ? 'pending' : 'editable',
        'editable' => !$isPending,
        'footer'   => $isPending ? 'message' : 'request',
        'form'     => $isPending ? null : [
            'action' => route('requests.store', ['date' => $date]),
            'method' => 'post',
        ],
        'canEdit'  => !$isPending,
    ];

    $viewData = $this->packDetail($attendance, $user, $date, $ui);
    $viewData['dateY']  = \Carbon\Carbon::parse($date)->isoFormat('YYYY年');
    $viewData['dateMD'] = \Carbon\Carbon::parse($date)->isoFormat('M月D日');

    return view('attendance.detail', $viewData);
}
}