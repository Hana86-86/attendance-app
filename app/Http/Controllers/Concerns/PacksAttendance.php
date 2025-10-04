<?php

namespace App\Http\Controllers\Concerns;

use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\User;

trait PacksAttendance
{
    /**
     * 分(整数) → "H:MM" 文字列
     */
    protected function toHM(?int $minutes): ?string
    {
        if ($minutes === null) return null;
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        return sprintf('%d:%02d', $h, $m);
    }

    /**
     * 勤怠詳細で使うデータを1か所で組み立て
     *
     * @param  Attendance|null $attendance
     * @param  User            $user
     * @param  string          $date   'YYYY-MM-DD'
     * @param  array           $ui
     * @return array
     */

    protected function packDetail(?Attendance $attendance, User $user, string $date, array $ui): array
    {
        // 出勤・退勤
        $clockIn  = $attendance?->clock_in  ? Carbon::parse($attendance->clock_in)->format('H:i')  : '';
        $clockOut = $attendance?->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : '';

        // 休憩（最大2枠）
        $b1in = $b1out = $b2in = $b2out = '';
        if ($attendance) {
            $bts = ($attendance->breakTimes ?? collect())->values();
            $b1in  = $bts->get(0)?->start?->format('H:i') ?? '';
            $b1out = $bts->get(0)?->end?->format('H:i')   ?? '';
            $b2in  = $bts->get(1)?->start?->format('H:i') ?? '';
            $b2out = $bts->get(1)?->end?->format('H:i')   ?? '';
        }

        return [
            'attendance' => $attendance,

            // 表示系
            'role'     => $ui['role']     ?? 'staff',     // 'staff' | 'admin'
            'status'   => $ui['status']   ?? 'editable',  // 'editable' | 'pending' | 'approved'
            'canEdit'  => $ui['editable'] ?? false,
            'footer'   => $ui['footer']   ?? null,
            'form'     => $ui['form']     ?? null,

            // ヘッダー
            'name'     => $user->name,
            'userId'   => $user->id,
            'date'     => $date,
            'note'     => $ui['note'] ?? null,

            // 明細
            'clockIn'   => $clockIn,
            'clockOut'  => $clockOut,
            'break1In'  => $b1in,
            'break1Out' => $b1out,
            'break2In'  => $b2in,
            'break2Out' => $b2out,
        ];
    }
}