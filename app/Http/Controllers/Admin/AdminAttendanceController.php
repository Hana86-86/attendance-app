<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller; //親クラス
use Illuminate\Http\Request;
use Carbon\Carbon;                   //日付操作
use App\Models\User;
use App\Models\Attendance;           //勤怠(休憩はリレーションで取得）
use Illuminate\Support\Collection;

class AdminAttendanceController extends Controller
{
    private function toHM(?int $min): string
    {
        if ($min === null) return '';
        $h = intdiv($min, 60);
        $m = $min % 60;
        return sprintf('%d:%02d', $h, $m);
    }

    public function index(string $date)
    {
        // 管理者は除外（role = 'user' のみ表示）
        $users = \App\Models\User::where('role', 'user')
        ->orderBy('id')
        ->get(['id','name']);


        $atts = Attendance::with('breakTimes')
            ->where('work_date', $date)
            ->get()
            ->keyBy('user_id');

        $list = [];
        foreach ($users as $u) {
            $att = $atts->get($u->id);  // 該当ユーザーの勤怠（無ければ null）

            $clockIn  = $att?->clock_in  ? Carbon::parse($att->clock_in)->format('H:i') : '';
            $clockOut = $att?->clock_out ? Carbon::parse($att->clock_out)->format('H:i') : '';
        // 休憩合計（分）
            $breakMin = 0;
            foreach ($att?->breakTimes ?? [] as $bt) {
                if ($bt->start && $bt->end) {
                    $breakMin += Carbon::parse($bt->start)->diffInMinutes(Carbon::parse($bt->end));
                }
            }
            $breakMin = $breakMin > 0 ? $breakMin : '';  // 0 なら空表示
        // 勤務合計（分）＝(退勤-出勤)-休憩）
            $workMin = null;
            if ($att?->clock_in && $att?->clock_out) {
                $total = Carbon::parse($att->clock_in)->diffInMinutes(Carbon::parse($att->clock_out));
                $workMin = max(0, $total - (int)($breakMin ?: 0));
            }
        // テーブル1行分
            $list[] = [
                'id'         => $u->id,
                'name'       => $u->name,
                'clock_in'   => $clockIn,
                'clock_out'  => $clockOut,
                'break_min'  => $breakMin ?: null,
                'break_hm'   => $this->toHM($breakMin),
                'work_min'   => $workMin,
                'work_hm'    => $this->toHM($workMin),
                // 詳細画面へのリンク（当日×ユーザー）
                'detail_url' => route('admin.attendances.show', ['date' => $date, 'id' => $u->id]),
            ];
        }

        return view('admin.attendance.index', [
            'title'   => Carbon::parse($date)->isoFormat('YYYY/MM/DD (dd)'),
            'date'    => $date,
            'prevDate'=> Carbon::parse($date)->subDay()->toDateString(),
            'nextDate'=> Carbon::parse($date)->addDay()->toDateString(),
            'list'    => $list, // 配列（行）のリスト
        ]);
    }

    public function show(string $date, int $id)
    {
        $user = User::findOrFail($id);

        $attendance = Attendance::with('breakTimes')
            ->where('user_id', $id)
            ->where('work_date', $date)
            ->first();

        $clockIn  = $attendance?->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : '';
        $clockOut  = $attendance?->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : '';

        $b1 = $attendance?->breakTimes[0] ?? null;
        $b2 = $attendance?->breakTimes[1] ?? null;

        $break1In  = $b1?->start ? Carbon::parse($b1->start)->format('H:i') : '';
        $break1Out  = $b1?->end ? Carbon::parse($b1->end)->format('H:i') : '';

        $break2In  = $b2?->start ? Carbon::parse($b2->start)->format('H:i') : '';
        $break2Out  = $b2?->end ? Carbon::parse($b2->end)->format('H:i') : '';

        $breaks[] = ['start' => '', 'end' => ''];

        $dt = Carbon::parse($date);
        $dateYear = $dt->isoFormat('YYYY年');
        $dateMD   = $dt->isoFormat('M月D日');


        $role    = 'admin';
        $status  = 'editable';
        $scanEdit = true;           //入力フィールドを編集モードで表示

        return view('admin.attendance.show', [
            'role'      => 'admin',     // Blade が編集モード/読み取りを判定
            'status'    => 'editable',  // 'pending' を渡すと読み取りに切替可
            'name'      => $user->name,
            'date'      => $date,
            'dateYear'  => $dateYear,
            'dateMD'    => $dateMD,

            // 時刻（H:i 文字列 or ''）
            'clockIn'   => $clockIn,
            'clockOut'  => $clockOut,
            'break1In'  => $break1In,
            'break1Out' => $break1Out,
            'break2In'  => $break2In,
            'break2Out' => $break2Out,
        ]);
    }
}
