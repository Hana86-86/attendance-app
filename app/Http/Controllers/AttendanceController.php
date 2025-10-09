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
    // 受け取った "YYYY-MM" をタイムゾーン付きでCarbon化
    $base  = Carbon::createFromFormat('Y-m', $month, config('app.timezone'));

    // 月初～月末の文字列（Y-m-d）を作る
    $start = $base->copy()->startOfMonth()->toDateString();
    $end   = $base->copy()->endOfMonth()->toDateString();

    // 画面ヘッダーなどに使う表示用文字列
    $titleYM   = $base->isoFormat('YYYY/MM');     // 例: 2025/10
    $prevMonth = $base->copy()->subMonth()->format('Y-m'); // 例: 2025-09
    $nextMonth = $base->copy()->addMonth()->format('Y-m'); // 例: 2025-11

    // 対象月の勤怠を取得（休憩も同時ロード）
    $rows = Attendance::with('breakTimes')
        ->where('user_id', Auth::id())
        ->whereBetween('work_date', [$start, $end])
        ->orderBy('work_date')
        ->get();

    // 日付キーの連想配列に変換
    //    work_date が "文字列" でも "Carbon" でも toDateString() に統一する保険
    $byDate = $rows->keyBy(function ($r) {
        $wd = $r->work_date;                              // 文字列かCarbon
        return $wd instanceof Carbon                      // Carbonならそのまま
            ? $wd->toDateString()
            : Carbon::parse($wd)->toDateString();         // 文字列ならparse
    });

    // 1日ずつループ。$end は文字列なので Carbon にして比較（保険）
    $list = [];
    for ($d = Carbon::parse($start); $d->lte(Carbon::parse($end)); $d->addDay()) {
        $date = $d->toDateString();        // 例: 2025-10-04
        $att  = $byDate->get($date);       // その日の勤怠（なければnull）

        // 出退勤のフォーマット（Carbonでも文字列でもOK）
        $ci = $att?->clock_in;             // 文字列かCarbonかnull
        $co = $att?->clock_out;

        $clockIn  = $ci ? ($ci instanceof Carbon ? $ci : Carbon::parse($ci))->format('H:i') : '';
        $clockOut = $co ? ($co instanceof Carbon ? $co : Carbon::parse($co))->format('H:i') : '';

        // 休憩合計（分）
        $breakMin = 0;
        if ($att) {
            foreach ($att->breakTimes as $bt) {
                if ($bt->start && $bt->end) {
                    // start/end が文字列でも分数差を計算
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
            $total   = $ciC->diffInMinutes($coC);               // 総分
            $workMin = max(0, $total - (int)($breakMin ?? 0));  // マイナス防止
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
        ->when(
            $attendance,
            fn ($q) => $q->where('attendance_id', $attendance->id),
            fn ($q) => $q->whereDate('requested_clock_in', $date)
        )
        ->where('status', 'pending')
        ->exists();

    $ui = [
    'role'    => 'staff',                              // スタッフ固定
    'status'  => $isPending ? 'pending' : 'editable',  // 申請中なら pending
    'canEdit' => !$isPending,                          // 申請中は編集不可
    'footer'  => $isPending ? 'message' : 'request',   // 申請中は注意文、通常は申請
    'form'    => [
        'action' => route('requests.store', ['date' => $date]),
        'method' => 'post',
    ],
];

    $viewData = $this->packDetail($attendance, $user, $date, $ui);
    $viewData['dateY']  = Carbon::parse($date)->isoFormat('YYYY年');
    $viewData['dateMD'] = Carbon::parse($date)->isoFormat('M月D日');

    return view('attendance.detail', $viewData);
}
}