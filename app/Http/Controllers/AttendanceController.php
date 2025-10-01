<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\Attendance\{ClockInRequest, ClockOutRequest, BreakInRequest, BreakOutRequest};
use App\Models\Attendance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Sri;

class AttendanceController extends Controller
{
    /**
     * 出勤画面（状態に応じてボタン切替）
     */
    public function create()
{
    $today = now()->toDateString();
    $state = 'not_working';

    $attendance = Attendance::with('breakTimes')
        ->where('user_id', Auth::id())
        ->where('work_date', $today)
        ->latest('id')
        ->first();

    if ($attendance) {
        if ($attendance->status === 'closed' || !is_null($attendance->clock_out)) {
            $state = 'closed'; // 退勤済
        } elseif ($attendance->hasOpenBreak()) {
            $state = 'on_break'; // ★ 休憩オープン優先
        } elseif ($attendance->status === 'working') {
            $state = 'working'; // 勤務中
        }
    }

    // バッジ（文言・色）
    $badgeTextMap = [
        'not_working' => '未出勤',
        'working'     => '勤務中',
        'on_break'    => '休憩中',
        'closed'      => '退勤済',
    ];
    $badgeClassMap = [
        'not_working' => 'badge badge-secondary',
        'working'     => 'badge badge-green',
        'on_break'    => 'badge badge-yellow',
        'closed'      => 'badge badge-blue',
    ];
    $badge = [
        'text'  => $badgeTextMap[$state]  ?? '未設定',
        'class' => $badgeClassMap[$state] ?? 'badge badge-secondary',
    ];

    return view('attendance.create', compact('attendance', 'today', 'state', 'badge'));
}

    /**
     * 出勤（1日1回）
     */
    public function clockIn(ClockInRequest $request)
    {
        $today = now()->toDateString();

        if (Attendance::where('user_id', Auth::id())->where('work_date', $today)->exists()) {
            return back()->withErrors(['clock_in' => '本日はすでに出勤済みです。']);
        }

        Attendance::create([
            'user_id'   => Auth::id(),
            'work_date' => $today,
            'clock_in'  => now(),
            'status'    => 'working',
        ]);

        return back()->with('status', '出勤しました');
    }

    /**
     * 休憩入（何回でも）…勤務中のみ、すでにオープン休憩が無いこと
     */
    public function breakIn(BreakInRequest $request)
    {
        $attendance = Attendance::where('user_id', Auth::id())
            ->where('work_date', now()->toDateString())
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

    /**
     * 休憩戻（何回でも）…オープン休憩があること
     */
    public function breakOut(BreakOutRequest $request)
    {
        $attendance = Attendance::where('user_id', Auth::id())
            ->where('work_date', now()->toDateString())
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

    /**
     * 退勤（1日1回）
     * オープン休憩があれば退勤させない（まず休憩戻を要求）
     */
    public function clockOut(ClockOutRequest $request)
    {
        $attendance = Attendance::where('user_id', Auth::id())
            ->where('work_date', now()->toDateString())
            ->first();

        if (!$attendance) {
            return back()->withErrors(['clock_out' => '本日はまだ出勤していません。']);
        }
        if ($attendance->clock_out) {
            return back()->withErrors(['clock_out' => '本日はすでに退勤済みです。']);
        }

        // ★ここが今回の直し：オープン休憩がある限りエラーにする
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
        //月の開始日と終了日を作成
        $base = Carbon::createFromFormat('Y-m', $month); // ← "YYYY-MM" で Carbon 生成
        $start = $base->copy()->startOfMonth();          // ← 月初
        $end   = $base->copy()->endOfMonth();            // ← 月末

        // タイトル表示用（YYYY/MM形式に）
        $titleYM = $base->isoFormat('YYYY/MM'); // ← Blade で使うため変数名を Blade と一致させる

        // 前月・翌月のリンク用パラメータ（YYYY-MM形式）
        $prevMonth = $base->copy()->subMonth()->format('Y-m'); // ← 1ヶ月戻す
        $nextMonth = $base->copy()->addMonth()->format('Y-m'); // ← 1ヶ月進める

        // 自分の勤怠を月範囲で取得（無い日は空白表示にしたいので map 整形）
        $rows = Attendance::with('breakTimes')                  // ← 休憩のリレーションも取得
            ->where('user_id', Auth::id())                      // ← 自分のデータのみ
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('work_date')
            ->get();

        // 日付をキーにする
        $byDate = $rows->keyBy('work_date');

        $list = [];

        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $date = $d->toDateString();
            $att  = $byDate->get($date); // その日の勤怠（なければ null）

            // 出退勤（H:i 文字列 or 空文字）
            $clockIn  = $att?->clock_in  ? Carbon::parse($att->clock_in)->format('H:i') : '';
            $clockOut = $att?->clock_out ? Carbon::parse($att->clock_out)->format('H:i') : '';

             // 休憩合計（分）
            $breakMin = 0;
        if ($att) {
            foreach ($att->breakTimes as $bt) {
                if ($bt->start && $bt->end) {
                    $breakMin += Carbon::parse($bt->start)->diffInMinutes(Carbon::parse($bt->end));
                }
            }
        }
            $breakMin = $breakMin > 0 ? $breakMin : '';

            // 勤務合計（分）…出退勤が揃っている時のみ数値、それ以外は空文字
        $workMin = '';
        if ($att && $att->clock_in && $att->clock_out) {
            $total   = Carbon::parse($att->clock_in)->diffInMinutes(Carbon::parse($att->clock_out));
            $workMin = max(0, $total - $breakMin);
        }

    $list[] = [
        'date'      => $date,                         // ← $att ではなく $date を使う
        'dow'       => $d->isoFormat('dd'),
        'clock_in'  => $clockIn,
        'clock_out' => $clockOut,
        'break_min' => $breakMin ?: '',               // 無い日は空白表示
        'work_min'  => $workMin === '' ? '' : $workMin,
        'detail_url'=> route('attendance.detail', ['date' => $date]),
    ];
}

        return view('attendance.list', [
            'titleYM'   => $titleYM,   //  Blade の @section('title', ...) で使用
            'prevMonth' => $prevMonth, //  「前月」リンク用
            'nextMonth' => $nextMonth, //  「翌月」リンク用
            'list'      => $list,      //  テーブル描画用
        ]);
    }
    public function show(string $date)
{
    $attendance = Attendance::with('breakTimes')
        ->where('user_id', Auth::id())
        ->where('work_date', $date)
        ->first();

    // 休憩入力行：既存レコード + 追加1行（空の入力用）
    $breaks = $attendance?->breakTimes?->map(function($bt){
        return [
            'start' => optional($bt->start)->format('H:i'),
            'end'   => optional($bt->end)->format('H:i'),
        ];
    })->toArray() ?? [];

    $breaks[] = ['start' => '', 'end' => '']; // 追加フィールド1つ

    return view('attendance.detail', [
        'name'       => Auth::user()->name,
        'date'       => $date,
        'attendance' => $attendance,
        'breaks'     => $breaks,
    ]);
}
}