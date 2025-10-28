<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\PacksAttendance;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AdminUserController extends Controller
{
    use PacksAttendance;
    use AuthorizesRequests;

    public function index()
    {
        $users = User::where('role', 'user')
            ->orderBy('id')
            ->get(['id','name','email']);

        $month = now()->format('Y-m');

        return view('admin.users.index', compact('users','month'));
    }

    public function attendances(int $id, string $month)
{
    $user  = User::findOrFail($id);

    $base  = Carbon::parse($month.'-01');
    $from  = $base->copy()->startOfMonth();
    $to    = $base->copy()->endOfMonth();

    $rows = Attendance::with('breakTimes')
        ->where('user_id', $id)
        ->whereBetween('work_date', [$from->toDateString(), $to->toDateString()])
        ->orderBy('work_date')
        ->get()
        ->keyBy(fn ($a) =>
            $a->work_date instanceof Carbon
                ? $a->work_date->toDateString()
                : Carbon::parse($a->work_date)->toDateString()
        );

    $list = [];
    for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
        $date = $d->toDateString();
        /** @var \App\Models\Attendance|null $att */
        $att  = $rows->get($date);

        // 出退勤（文字列）
        $clockIn  = $att?->clock_in  ? Carbon::parse($att->clock_in)->format('H:i') : '';
        $clockOut = $att?->clock_out ? Carbon::parse($att->clock_out)->format('H:i') : '';

        $breakMin = $att?->break_minutes;
        $workMin  = $att?->work_minutes;

        $list[] = [
            'user_id'   => $user->id,
            'name'      => $user->name,
            'clock_in'  => $clockIn,
            'clock_out' => $clockOut,

            'break_min' => $breakMin,
            'break_hm'  => $att?->break_hm ?? '—',
            'work_min'  => $workMin,
            'work_hm'   => $att?->work_hm  ?? '—',

            'work_date'  => $date,
            'detail_url' => route('admin.attendances.show', [
                'date' => $date,
                'id'   => $user->id,
            ]),
        ];
    }

    return view('admin.users.attendances', [
        'user'      => $user,
        'month'     => $base->format('Y-m'),
        'prevMonth' => $base->copy()->subMonth()->format('Y-m'),
        'nextMonth' => $base->copy()->addMonth()->format('Y-m'),
        'list'      => $list,
    ]);
}
    public function exportMonth(int $id, string $month)
    {
        // 認可
        $user = User::findOrFail($id);
        $this->authorize('view-attendance-of-user', $user);

        // 期間設定
        $base  = Carbon::createFromFormat('Y-m', $month, config('app.timezone'));
        $start = $base->copy()->startOfMonth()->toDateString();
        $end   = $base->copy()->endOfMonth()->toDateString();

        // 勤怠データ取得
        $rows = Attendance::with('breakTimes')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$start, $end])
            ->orderBy('work_date')
            ->get()
            ->keyBy(fn ($r) => ($r->work_date instanceof Carbon)
                ? $r->work_date->toDateString()
                : Carbon::parse($r->work_date)->toDateString()
            );

        $filename = sprintf('attendance_%s_%s.csv', $user->id, $base->format('Ym'));

        return response()->streamDownload(function () use ($start, $end, $rows, $user) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM
            fwrite($out, "\xEF\xBB\xBF");
            // ヘッダー行
            fputcsv($out, ['日付','出勤','退勤','休憩','合計','備考']);
            // ユーティリティ
            $hm = function (?int $minutes) {
                if ($minutes === null) return '0:00';
                $h = intdiv(max(0,$minutes), 60);
                $m = max(0,$minutes) % 60;
                return sprintf('%d:%02d', $h, $m);
            };

            // 1日ずつ出力
            for ($d = Carbon::parse($start); $d->lte(Carbon::parse($end)); $d->addDay()) {
                $date = $d->toDateString();
                $att  = $rows->get($date);

                // 表示文字列
                $ci = $att?->clock_in;
                $co = $att?->clock_out;
                $clockIn  = $ci ? Carbon::parse($ci)->format('H:i') : '';
                $clockOut = $co ? Carbon::parse($co)->format('H:i') : '';

                // 休憩合計（分）
                $breakMin = 0;
                if ($att) {
                    foreach ($att->breakTimes as $bt) {
                        if ($bt->start && $bt->end) {
                            $breakMin += Carbon::parse($bt->start)->diffInMinutes(Carbon::parse($bt->end));
                        }
                    }
                }
                $breakMin = $breakMin ?: 0;

                // 勤務合計（分）
                $workMin = 0;
                if ($ci && $co) {
                    $total   = Carbon::parse($ci)->diffInMinutes(Carbon::parse($co));
                    $workMin = max(0, $total - $breakMin);
                }

                // 備考
                $reason = $att?->reason ?? '';

                fputcsv($out, [
                    Carbon::parse($date)->format('Y/m/d(D)'),
                    $clockIn,
                    $clockOut,
                    $hm($breakMin),
                    $hm($workMin),
                    $reason,
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}



