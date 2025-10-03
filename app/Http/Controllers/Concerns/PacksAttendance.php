<?php

namespace App\Http\Controllers\Concerns;

use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\User;

trait PacksAttendance
{
    /**
     * 勤怠詳細画面で使う全変数を1か所で組み立てる
     *
     * @param  Attendance|null $attendance  当日の勤怠(なければ null)
     * @param  User            $user        対象ユーザー
     * @param  string          $date        'YYYY-MM-DD'
     * @param  array           $ui          画面モード・フッター等 ['role','status','editable','footer','form'...]
     * @return array
     */
    protected function packDetail($attendance, User $user, string $date, array $ui): array
    {
        // 出勤・退勤
        $clockIn  = $attendance?->clock_in  ? Carbon::parse($attendance->clock_in)->format('H:i')  : '';
        $clockOut = $attendance?->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : '';

        // 休憩（最大2枠を画面に出す）
        $b1in = $b1out = $b2in = $b2out = '';
        if ($attendance) {
            $bts = collect($attendance->breakTimes ?? []);
            if ($bts->get(0)) {
                $b1in  = optional($bts[0]->start)->format('H:i') ?? '';
                $b1out = optional($bts[0]->end)->format('H:i')   ?? '';
            }
            if ($bts->get(1)) {
                $b2in  = optional($bts[1]->start)->format('H:i') ?? '';
                $b2out = optional($bts[1]->end)->format('H:i')   ?? '';
            }
        }

        return [
            // 表示系
            'role'      => $ui['role']       ?? 'staff',              // 'staff' | 'admin'
            'status'    => $ui['status']     ?? 'editable',           // 'editable' | 'pending' | 'approved'（画面状態）
            'canEdit'   => $ui['editable']   ?? false,                // 入力可能かどうか
            'footer'    => $ui['footer']     ?? null,                 // 右下ボタンの種別
            'form'      => $ui['form']       ?? null,                 // POST 先

            // ヘッダー
            'name'      => $user->name,
            'date'      => $date,

            // 明細（Blade は存在する変数だけを参照する、そのまま受け取る変数名）
            'clockIn'   => $clockIn,
            'clockOut'  => $clockOut,
            'break1In'  => $b1in,
            'break1Out' => $b1out,
            'break2In'  => $b2in,
            'break2Out' => $b2out,
        ];
    }
}