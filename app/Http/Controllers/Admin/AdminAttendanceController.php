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
use Illuminate\Support\Facades\DB;

class AdminAttendanceController extends Controller
{
    use PacksAttendance;

    public function index(string $date)
    {
        // 管理者は除外（role = 'user' のみ表示）
        $users = User::where('role', 'user')
        ->orderBy('id')
        ->get(['id','name']);


        $atts = Attendance::with('breakTimes')
            ->where('work_date', today())
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
            $dateYmd = Carbon::parse($date)->format('Y-m-d');
        // テーブル1行分
            $list[] = [
                'user_id'   => $u->id,
                'name'      => $u->name,
                'clock_in'  => $clockIn,                 // 'H:i' or ''
                'clock_out' => $clockOut,                // 'H:i' or ''
                'break_min' => $breakMin ?: null,        // 数字 or null
                'break_hm'  => $this->toHM(is_null($breakMin) ? null : (int)$breakMin),
                'work_min'  => $workMin,                 // 数字 or null
                'work_hm'   => $this->toHM(is_null($workMin)  ? null : (int)$workMin),    // 'h:mm' or ''
                'work_date' => $dateYmd,            // ★ Blade が参照
                'detail_url' => route('admin.attendances.show', [
                'date' => $dateYmd,
                'id'   => $u->id,
            ]),
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
    // 対象ユーザー 勤怠を取得
    $user = User::findOrFail($id);

    $attendance = Attendance::with('breakTimes')
        ->where('user_id', $user->id)
        ->whereDate('work_date', $date)
        ->first();

    $attendanceId = optional($attendance)->id;

    $pendingRequest = StampCorrectionRequest::query()
        ->where('user_id', $user->id)
        ->when($attendanceId, fn ($q) => $q->where('attendance_id', $attendanceId))
        ->latest('id')
        ->first();
    $status     = 'editable';
    $footer     = 'admin_update';
    $canEdit    = true;

    if ($pendingRequest) {
        if ($pendingRequest->status === 'pending') {
            $status  = 'pending';
            $footer  = 'approve';
            $canEdit = true;
        } elseif ($pendingRequest->status === 'approved') {
            $status  = 'approved';
            $footer  = 'approved';    // ボタン「承認済み」
            $canEdit = false;
        }
    }
    $ui = [
        'role'     => 'admin',
        'status'   => $status,        // 'editable' | 'pending' | 'approved'
        'editable' => $canEdit,       // 入力可否
        'footer'   => $footer,        // 'admin_update' | 'approve' | 'approved'
        'form'     => match ($footer) {
            'approve'      => ['action' => route('admin.requests.approve', ['id' => $requestId]), 'method' => 'post'],
            'admin_update' => ['action' => route('admin.attendances.update', ['date' => $date, 'id' => $user->id]), 'method' => 'post'],
            default        => null,
        },
    ];

    return view('attendance.detail', array_merge($this->packDetail($attendance, $user, $date, $ui),
    [
        'dateY'  => \Carbon\Carbon::parse($date)->isoFormat('YYYY年'),
        'dateMD' => \Carbon\Carbon::parse($date)->isoFormat('M月D日'),
    ]
));
}

    public function update(string $date, int $id, UpdateRequest $request)
    {
        $data   = $request->validated();
        $breaks = $data['breaks'] ?? [];

        return DB::transaction(function () use ($date, $id, $data, $breaks) {
            $attendance = Attendance::firstOrCreate(
                ['user_id' => $id, 'work_date' => $date]
            );

            $attendance->clock_in  = $data['clock_in']  ?? null;
            $attendance->clock_out = $data['clock_out'] ?? null;

            if (!empty($breaks[0])) {
                $attendance->setBreakTimes($breaks);
            }
            $attendance->note = $data['note'];
            $attendance->save();

            return back()->with('success', '勤怠を更新しました。');
        });
    }
}
