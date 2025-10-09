<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use App\Http\Controllers\Concerns\PacksAttendance;

class AdminUserController extends Controller
{
    use PacksAttendance;

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

        // そのユーザーの当月勤怠をまとめて取得
        $atts = Attendance::with('breakTimes')
            ->where('user_id', $id)
            ->whereBetween('work_date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->keyBy('work_date');

        $list = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $date = $d->toDateString();
            $att  = $atts->get($date);

            $clockIn  = $att?->clock_in  ? Carbon::parse($att->clock_in)->format('H:i') : '';
            $clockOut = $att?->clock_out ? Carbon::parse($att->clock_out)->format('H:i') : '';
            $breakMin = 0;
            $workMin  = ($att?->clock_in && $att?->clock_out)
                        ? Carbon::parse($att->clock_in)->diffInMinutes(Carbon::parse($att->clock_out)) - $breakMin
                        : null;

            $list[] = [
                'user_id'   => $user->id,
                'name'      => $user->name,
                'clock_in'  => $clockIn,
                'clock_out' => $clockOut,
                'break_min' => $breakMin ?: null,
                'break_hm'  => $this->toHM(is_null($breakMin) ? null : (int)$breakMin),
                'work_min'  => $workMin,
                'work_hm'   => $this->toHM(is_null($workMin)  ? null : (int)$workMin),
                'work_date' => $date,
                'detail_url' => route('admin.attendances.show', [
                'date' => $date,
                'id'   => $user->id,
            ]),
        ];
        }

        return view('admin.attendance.index', [
            'title'    => $base->isoFormat('YYYY/MM'),
            'date'     => $base->toDateString(),           // ナビ用に基準日
            'prevDate' => $base->copy()->subMonth()->toDateString(),
            'nextDate' => $base->copy()->addMonth()->toDateString(),
            'list'     => $list,
        ]);
    }
}

