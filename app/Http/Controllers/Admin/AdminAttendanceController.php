<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\PacksAttendance;
use App\Http\Requests\Attendance\UpdateRequest;
use App\Http\Controllers\Controller; //親クラス
use Carbon\Carbon;                   //日付操作
use App\Models\User;
use App\Models\Attendance;           //勤怠(休憩はリレーションで取得）
use Illuminate\Support\Collection;
use App\Models\StampCorrectionRequest; 

class AdminAttendanceController extends Controller
{
    use PacksAttendance;

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
            $breakMin = $breakMin > 0 ? (int)$breakMin :0;  // 0 なら空表示
        // 勤務合計（分）＝(退勤-出勤)-休憩）
            $workMin = null;
            if ($att?->clock_in && $att?->clock_out) {
                $total = Carbon::parse($att->clock_in)->diffInMinutes(Carbon::parse($att->clock_out));
                $workMin = max(0, $total - $breakMin);
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

        $attendanceId = optional($attendance)->id;

        $requestId = StampCorrectionRequest::where('user_id', $id)
            ->where('status', 'pending')
            ->when($attendanceId,
            fn($q) => $q->where('attendance_id', $attendanceId),
            fn($q) => $q->whereDate('requested_clock_in', $date)
            )
            ->latest('id')
            ->value('id');   // null の可能性あり（承認待ちが無い場合）

        $ui = [
            'role'     => 'admin',
            'status'   => 'editable',
            'editable' => true,
            'footer'   => '承認',
        'form'     => [
            'action' => $requestId ? route('requests.approve', ['id' => $requestId]) : null,
            'method' => 'post',
        ],
        // 2個目フッター：直接勤怠更新
            'footer'   => '修正',
            'form'     => ['action' => route('admin.attendances.update', ['date'=>$date, 'id'=>$id]), 'method'=>'post'],
    ];
        return view('attendance.detail', $this->packDetail($attendance, $user, $date, $ui));

    }
    public function update(string $date, int $id, UpdateRequest $request)
    {
        // ここは既存の更新ロジックを利用（直接勤怠を書き換え）
        // …（省略・既存の保存処理を呼ぶ）
        return back()->with('success','勤怠を修正しました。');
    }
    
}
