<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\StampCorrectionRequest;
use App\Models\User;
use Carbon\Carbon;

trait PacksAttendance
{
    // 分 → "H:MM"（nullならnullのまま返す。表示フォーマットはBlade直前で決められるようにする）
    protected function toHM(?int $minutes): ?string
    {
        if ($minutes === null) return null;
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        return sprintf('%d:%02d', $h, $m);
    }

    protected function packDetail(
    Attendance $att,
    User $user,
    string $dateYmd,
    array $ui                       // ['role','status','canEdit','footer','form'] 等
): array {

    $date = Carbon::parse($dateYmd);

    $out = [
        'attendance' => $att,
        'name'       => $user->name,
        'date'       => $date->toDateString(),
        'dateY'      => $date->isoFormat('YYYY年'),
        'dateMD'     => $date->isoFormat('M月D日'),
    ];

    $out['clockIn']  = $att->clock_in  ? Carbon::parse($att->clock_in)->format('H:i')  : '';
    $out['clockOut'] = $att->clock_out ? Carbon::parse($att->clock_out)->format('H:i') : '';

    $bt = $att->relationLoaded('breakTimes')
    ? $att->getRelation('breakTimes')
    : $att->breakTimes()->orderBy('start')->get();
    $bt = collect($bt)->sortBy('start')->values();

    $pairs = collect($bt)->map(function ($b) {
    $rawStart = $b->start ?? null;
    $rawEnd   = $b->end   ?? null;

    $s = $rawStart ? (Carbon::parse($rawStart)->format('H:i')) : '';
    $e = $rawEnd   ? (Carbon::parse($rawEnd)->format('H:i'))   : '';

    return ['start' => $s, 'end' => $e];
})

    ->filter(fn($p) => $p['start'] !== '' || $p['end'] !== '')
    ->values();

    for ($i = count($pairs); $i < 2; $i++) { $pairs[$i] = ['start' => '', 'end' => '']; }
        $out['break1In']  = $pairs[0]['start'] ?? '';
        $out['break1Out'] = $pairs[0]['end']   ?? '';
        $out['break2In']  = $pairs[1]['start'] ?? '';
        $out['break2Out'] = $pairs[1]['end']   ?? '';

    $out['reason'] = $att->reason ?? '';

    return array_merge($out, $ui);
}


    protected function overlayAttendanceWithRequest(
        ?Attendance $attendance,
        ?StampCorrectionRequest $req,
        int $userId,
        string $dateYmd
    ): Attendance {
        $att = $attendance ?: new Attendance([
        'user_id'   => $userId,
        'work_date' => $dateYmd,
    ]);

    if ($req && $req->status === 'pending') {
        if ($req->requested_clock_in)  $att->clock_in  = $req->requested_clock_in;
        if ($req->requested_clock_out) $att->clock_out = $req->requested_clock_out;
        // 休憩：申請の配列を反映
        $rb = $req->requested_break;
        if (is_string($rb)) {
            $decoded = json_decode($rb, true);
            $rb = is_array($decoded) ? $decoded : [];
        } elseif (!is_array($rb)) {
            $rb = [];
        }

    $rb = array_values(array_filter((array)$rb, fn($b) =>
        !empty($b['start']) || !empty($b['end'])
    ));
    $rb = array_map(fn($b) => [
        'start' => $b['start'] ?? null,
        'end'   => $b['end']   ?? null,
        ], $rb);

    $fake = collect($rb)->map(fn ($b) => new BreakTime([
        'start' => $b['start'],
        'end'   => $b['end'],
    ]));
    $att->setRelation('breakTimes', $fake);
        // 備考プレビュー
        if (!empty($req->reason)) {
            $att->reason = $req->reason;
        }
    } else {
        // 合成しないときは実データの休憩を読み込む
        $att->loadMissing('breakTimes');
    }

    return $att;
}
}