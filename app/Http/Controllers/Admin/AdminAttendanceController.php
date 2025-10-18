<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\PacksAttendance;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\UpdateRequest;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminAttendanceController extends Controller
{
    use PacksAttendance;

    public function index(string $date)
    {
        $users = User::where('role', 'user')->orderBy('id')->get(['id','name']);

    $atts = Attendance::with('breakTimes')
        ->whereDate('work_date', $date)
        ->get()
        ->keyBy('user_id');

    $list = [];
    foreach ($users as $u) {
        $att = $atts->get($u->id);

        $clockIn  = $att?->clock_in  ? Carbon::parse($att->clock_in)->format('H:i') : '';
        $clockOut = $att?->clock_out ? Carbon::parse($att->clock_out)->format('H:i') : '';

        // 休憩合計（分）
        $breakMin = 0;
        foreach ($att?->breakTimes ?? [] as $bt) {
            if ($bt->start && $bt->end) {
                $breakMin += Carbon::parse($bt->start)->diffInMinutes(Carbon::parse($bt->end));
            }
        }
        $breakMin = $breakMin ?: null;

        // 勤務合計（分）
        $workMin = null;
        if ($att?->clock_in && $att?->clock_out) {
            $total   = Carbon::parse($att->clock_in)->diffInMinutes(Carbon::parse($att->clock_out));
            $workMin = max(0, $total - (int)($breakMin ?? 0));
        }
        $breakHM = $this->toHM($breakMin);
        $workHM  = $this->toHM($workMin);

        $dateYmd = Carbon::parse($date)->format('Y-m-d');

        $list[] = [
            'user_id'    => $u->id,
            'name'       => $u->name,
            'clock_in'   => $clockIn,
            'clock_out'  => $clockOut,
            'break_min'  => $breakMin,
            'break_hm'   => $breakHM,
            'work_hm'    => $workHM,
            'work_min'   => $workMin,
            'work_date'  => $dateYmd,
            'detail_url' => route('admin.attendances.show', [
                'date' => $dateYmd,
                'id'   => $u->id,
            ]),
        ];
    }

    return view('admin.attendance.index', [
        'title'    => Carbon::parse($date)->isoFormat('YYYY/MM/DD (dd)'),
        'date'     => $date,
        'prevDate' => Carbon::parse($date)->subDay()->toDateString(),
        'nextDate' => Carbon::parse($date)->addDay()->toDateString(),
        'list'     => $list,
    ]);
}

    public function show(string $date, int $id)
{
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

    // 画面状態
    $status  = 'editable';
    $footer  = 'admin_update';
    $canEdit = true;

    if ($pendingRequest) {
        if ($pendingRequest->status === 'pending') {
            $status  = 'pending';
            $footer  = 'approve';
            $canEdit = true;
        } elseif ($pendingRequest->status === 'approved') {
            $status  = 'approved';
            $footer  = 'approved';
            $canEdit = false;
        }
    }

    $requestId = optional($pendingRequest)->id;

    $ui = [
        'role'    => 'admin',
        'status'  => $status,       // 'editable' | 'pending' | 'approved'
        'canEdit' => $canEdit,
        'footer'  => $footer,       // 'admin_update' | 'approve' | 'approved'
        'form'    => match ($footer) {
            'approve'      => ['action' => route('admin.requests.approve'), 'method' => 'post'],
            'admin_update' => ['action' => route('admin.attendances.update', ['date' => $date, 'id' => $user->id]), 'method' => 'post'],
            default        => null,
        },
    ];

    $vars = $this->packDetail($attendance, $user, $date, $ui);
    $vars['detailId'] = $requestId; // hidden 用

    $vars['title']    = Carbon::parse($date)->isoFormat('YYYY/MM/DD (dd)');
    $vars['date']     = $date;
    $vars['prevDate'] = Carbon::parse($date)->subDay()->toDateString();
    $vars['nextDate'] = Carbon::parse($date)->addDay()->toDateString();

    // 詳細でも index.blade を使い回す
    return view('admin.attendance.index', $vars);
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
            $attendance->reason = $data['reason'];
            $attendance->save();

            return back()->with('success', '勤怠を更新しました。');
        });
    }
}
