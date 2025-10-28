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
    $date = Carbon::parse($date)->toDateString();

    $users = User::where('role', 'user')
        ->orderBy('id')
        ->with([
            'attendances' => function ($q) use ($date) {
                $q->whereDate('work_date', $date)
                    ->with('breakTimes');
            }
        ])
        ->get(['id','name']);

    $list = $users->map(function ($u) {

        $att = $u->attendances->first();

        $row = new \stdClass();

        $row->user_id = $u->id;
        $row->user    = $u;

        $row->clock_in  = $att?->clock_in  ? Carbon::parse($att->clock_in)  : null;
        $row->clock_out = $att?->clock_out ? Carbon::parse($att->clock_out) : null;

        $row->break_hm = $att?->break_hm ?? '0:00';
        $row->work_hm  = $att?->work_hm  ?? '0:00';

        return $row;
    });

    return view('admin.attendance.index', [
        'isDetail' => false,
        'title' => Carbon::parse($date)->isoFormat('YYYY/MM/DD (ddd)'),
        'date'  => $date,
        'prev'  => Carbon::parse($date)->subDay()->toDateString(),
        'next'  => Carbon::parse($date)->addDay()->toDateString(),
        'list'  => $list,
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

    $latestRequest = StampCorrectionRequest::query()
        ->where('user_id', $user->id)
        ->where(function ($q) use ($attendanceId, $date) {
            if ($attendanceId) {
                $q->where('attendance_id', $attendanceId)
                  ->orWhere(function ($qq) use ($date) {
                      $qq->whereNull('attendance_id')
                         ->where(function ($qqq) use ($date) {
                             $qqq->whereDate('requested_clock_in',  $date)
                                 ->orWhereDate('requested_clock_out', $date);
                         });
                  });
            } else {
                $q->where(function ($qq) use ($date) {
                    $qq->whereDate('requested_clock_in',  $date)
                       ->orWhereDate('requested_clock_out', $date);
                });
            }
        })
        ->latest('id')
        ->first();

    // --- UI（右下ボタンなど）
    $status  = 'editable';
    $footer  = 'admin_update';
    $canEdit = true;

    if ($latestRequest) {
        if ($latestRequest->status === 'pending') {
            $status  = 'pending';
            $footer  = 'approve';     // 承認ボタン
            $canEdit = true;          // 管理者は承認も可
        } elseif ($latestRequest->status === 'approved') {
            $status  = 'approved';
            $footer  = 'approved';    // グレー表示
            $canEdit = false;
        }
    }

    // --- 画面に出す勤怠
    // 承認待ちのときだけ「申請内容」を合成してプレビュー、それ以外は実データのみ
    if ($latestRequest && $latestRequest->status === 'pending') {
        $attForView = $this->overlayAttendanceWithRequest($attendance, $latestRequest, $user->id, $date);
    } else {
        $attForView = $attendance ?: new Attendance(['user_id' => $user->id, 'work_date' => $date]);
        $attForView->loadMissing('breakTimes'); // 実データの休憩を確実に読む
    }

    $ui = [
        'role'    => 'admin',
        'status'  => $status,
        'canEdit' => $canEdit,
        'footer'  => $footer,
        'form'    => match ($footer) {
            'approve'      => ['action' => route('admin.requests.approve'), 'method' => 'post'],
            'admin_update' => ['action' => route('admin.attendances.update', ['date' => $date, 'id' => $user->id]), 'method' => 'post'],
            default        => null,
        },
    ];

    $vars = $this->packDetail($attForView, $user, $date, $ui);
    $vars['detailId'] = optional($latestRequest)->id;
    $vars['title']    = Carbon::parse($date)->isoFormat('YYYY/MM/DD (dd)');
    $vars['date']     = $date;
    $vars['prevDate'] = Carbon::parse($date)->subDay()->toDateString();
    $vars['nextDate'] = Carbon::parse($date)->addDay()->toDateString();

    return view('admin.attendance.index', array_merge($vars, ['isDetail' => true]));
}

    public function update(string $date, int $id, UpdateRequest $request)
{
    $data   = $request->validated();
    $breaks = $data['breaks'] ?? [];

    return DB::transaction(function () use ($date, $id, $data, $breaks) {
        $attendance = Attendance::firstOrCreate(
            ['user_id' => $id, 'work_date' => $date]
        );

        // 出退勤を更新
        $attendance->clock_in  = $data['clock_in']  ?? null;
        $attendance->clock_out = $data['clock_out'] ?? null;

        // 休憩削除 → 再作成
        $attendance->breakTimes()->delete();

        foreach ($breaks as $bt) {
            $start = $bt['start'] ?? null;
            $end   = $bt['end'] ?? null;

            // 開始も終了も空ならスキップ
            if (!$start && !$end) {
                continue;
            }

            $attendance->breakTimes()->create([
                'start' => $start ? Carbon::parse($start) : null,
                'end'   => $end   ? Carbon::parse($end)   : null,
            ]);
        }

        $attendance->reason = $data['reason'] ?? null;
        $attendance->save();

        return redirect()
                ->route('admin.attendances.show', ['date' => $date, 'id' => $id])
                ->with('success', '勤怠を更新しました。');
        });
}
}