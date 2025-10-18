<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;

trait PacksAttendance
{
    /** 分 → "H:MM" 文字列にするヘルパ */
    protected function toHM(?int $minutes): ?string
    {
        if ($minutes === null) return null;
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        return sprintf('%d:%02d', $h, $m);
    }

    /**
     * 勤怠詳細カードで使う配列を1か所で組み立てる
     *
     * @param  Attendance|null $attendance  勤怠（null可）
     * @param  User            $user        対象ユーザー
     * @param  string          $date        'YYYY-MM-DD'
     * @param  array           $ui          画面状態
     * @return array           Bladeにそのまま渡せる形
     */
    protected function packDetail(?Attendance $attendance, User $user, string $date, array $ui): array
    {
        // 出退勤時刻
        $clockIn  = $attendance?->clock_in  ? Carbon::parse($attendance->clock_in)->format('H:i')  : '';
        $clockOut = $attendance?->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : '';

        $bts   = $attendance?->breakTimes?->sortBy('start')->values() ?? collect();
        $b1in  = optional($bts->get(0)?->start)->format('H:i') ?? '';
        $b1out = optional($bts->get(0)?->end  )->format('H:i') ?? '';
        $b2in  = optional($bts->get(1)?->start)->format('H:i') ?? '';
        $b2out = optional($bts->get(1)?->end  )->format('H:i') ?? '';

        $role = $ui['role'] ?? (request()->routeIs('admin.*') ? 'admin' : 'staff');
        $status  = $ui['status']  ?? 'editable';                // 'editable' | 'pending' | 'approved'
        $canEdit = (bool)($ui['canEdit'] ?? ($ui['editable'] ?? false)); // ← 'editable' を受けても 'canEdit' に寄せる
        $footer  = $ui['footer']  ?? 'request';                 // 'request'|'message'|'admin_update'|'approve'|'approved'
        $form    = $ui['form']    ?? null;                      // ['action'=>..., 'method'=>...]

        return [
            'role'    => $role,
            'status'  => $status,
            'canEdit' => $canEdit,
            'footer'  => $footer,
            'form'    => $form,

            'name'    => $user->name,
            'dateY'   => Carbon::parse($date)->isoFormat('YYYY年'),
            'dateMD'  => Carbon::parse($date)->isoFormat('M月D日'),

            'clockIn'  => $clockIn,
            'clockOut' => $clockOut,
            'break1In' => $b1in,
            'break1Out'=> $b1out,
            'break2In' => $b2in,
            'break2Out'=> $b2out,
            'reason' => $attendance->reason ?? '',
            'attendance' => $attendance,
            'user_id'    => $user->id,
            'date'       => $date,
        ];
    }
}