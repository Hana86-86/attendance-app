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

        // ★当日の勤怠を user と breakTimes 付きで取得（計算はモデルのアクセサが担当）
        $list = Attendance::with(['breakTimes', 'user'])
            ->whereDate('work_date', $date)
            ->orderBy('user_id')
            ->get();

        return view('admin.attendance.index', [
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
