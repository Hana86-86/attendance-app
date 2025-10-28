<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttendanceMonthSeeder extends Seeder
{
    public function run(): void
    {
        $includeWeekend = false;

        $users = User::where('role', 'user')->get();
        if ($users->isEmpty()) return;

        $start = Carbon::now()->startOfMonth();
        $end   = Carbon::now();

        DB::transaction(function () use ($users, $start, $end, $includeWeekend) {

            $now = now();

            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {

                if (!$includeWeekend && $d->isWeekend()) {
                    continue;
                }

                foreach ($users as $u) {

                    // 10%の確率で欠勤
                    if (mt_rand(1, 100) <= 10) {
                        continue;
                    }

                    // 出勤 9:00〜9:40 の範囲で決定
                    $clockIn = $d->copy()->setTime(9, 0)->addMinutes(mt_rand(0, 40));

                    // 退勤 17:40〜18:10 の範囲で決定
                    $clockOut = $d->copy()->setTime(18, 0)->addMinutes(mt_rand(-20, 10));

                    // 退勤が出勤より前にならないように調整
                    if ($clockOut->lte($clockIn)) {
                    $clockOut = $clockIn->copy()->addHours(8);
                    }

                    // 総休憩 60〜90分
                    $totalBreak = mt_rand(60, 90);

                    // 休憩パターンを決定
                    $breaks = [];
                    if (mt_rand(1, 100) <= 60) {
                        $firstLen  = mt_rand(30, min(60, $totalBreak - 30));
                        $secondLen = $totalBreak - $firstLen;

                        // 昼休憩（12:00付近）
                        $b1Start = $d->copy()->setTime(12, mt_rand(0, 30));
                        $b1End   = $b1Start->copy()->addMinutes($firstLen);

                        // 夕方休憩（16:00付近）
                        $b2Start = $d->copy()->setTime(16, mt_rand(0, 30));
                        $b2End   = $b2Start->copy()->addMinutes($secondLen);

                        $breaks = [
                            ['start' => $b1Start->toTimeString(), 'end' => $b1End->toTimeString()],
                            ['start' => $b2Start->toTimeString(), 'end' => $b2End->toTimeString()],
                        ];
                    } else {
                        $bStart = $d->copy()->setTime(12, mt_rand(0, 30));
                        $bEnd   = $bStart->copy()->addMinutes($totalBreak);
                        $breaks = [
                            ['start' => $bStart->toTimeString(), 'end' => $bEnd->toTimeString()],
                        ];
                    }

                    if ($d->isToday()) {
                    continue;
                    }


                    $att = Attendance::updateOrCreate(
                    ['user_id' => $u->id, 'work_date' => $d->toDateString()],
                    [
                        'clock_in'  => $clockIn->toTimeString(),
                        'clock_out' => $clockOut->toTimeString(),
                        'reason'    => '遅延のため',
                    ]
                    );

                    // 既存の休憩を入れ替え
                    $att->breakTimes()->delete();
                    foreach ($breaks as $bk) {
                        if (!empty($bk['start']) && !empty($bk['end'])) {
                            $att->breakTimes()->create($bk);
                        }
                    }
                }
            }
        });
    }
}