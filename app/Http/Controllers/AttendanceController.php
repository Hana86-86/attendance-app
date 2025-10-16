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

    $dateY  = Carbon::parse($date)->isoFormat('YYYY年');
    $dateMD = Carbon::parse($date)->isoFormat('M月D日 (ddd)');

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
        return back();
    }

    $attendance->update([
        'clock_in' => now(),
        'status'   => 'working',
    ]);

    return back();
}

    public function breakIn(BreakInRequest $request)
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

    public function breakOut(BreakOutRequest $request)
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

    public function clockOut(ClockOutRequest $request)
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

        $attendance = Attendance::with('breakTimes')
            ->where('user_id', $user->id)
            ->whereDate('work_date', $date)
            ->first();

    $isPending = StampCorrectionRequest::query()
    ->where('user_id', $user->id)
    ->where(function ($q) use ($attendance, $date) {
        if ($attendance) {
            // 勤怠レコードがある日は attendance_id 一致で限定
            $q->where('attendance_id', $attendance->id);
        } else {
            // 勤怠レコードがない日は日付一致で限定
            $q->where(function ($qq) use ($date) {
                $qq->whereDate('requested_clock_in',  $date)
                    ->orWhereDate('requested_clock_out', $date);
            });
        }
    })
        ->where('status', 'pending')
        ->exists();

    $ui = [
    'role'    => 'staff',
    'status'  => $isPending ? 'pending' : 'editable',  // 申請中なら pending
    'canEdit' => !$isPending,                          // 申請中は編集不可
    'footer'  => $isPending ? 'message' : 'request',   // 申請中は注意文、通常は申請
    'form'    => [
        'action' => route('requests.store', ['date' => $date]),'method' => 'post',
    ],
];

    $viewData = $this->packDetail($attendance, $user, $date, $ui);
    $viewData['dateY']  = Carbon::parse($date)->isoFormat('YYYY年');
    $viewData['dateMD'] = Carbon::parse($date)->isoFormat('M月D日');

    return view('attendance.detail', $viewData);
}
}