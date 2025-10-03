<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AdminUserController extends Controller
{
    // 検索なし・全件表示
    public function index()
    {
        $users = User::where('role', 'user')  // 管理者は除外
            ->orderBy('id')
            ->get(['id','name','email']);

        $month = now()->format('Y-m');        // 月次リンク用（当月）

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

        // 画面に渡す行データ（管理者一覧の形に合わせて生成）
        $list = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $date = $d->toDateString();
            $att  = $atts->get($date);

            $clockIn  = $att?->clock_in  ? Carbon::parse($att->clock_in)->format('H:i') : '';
            $clockOut = $att?->clock_out ? Carbon::parse($att->clock_out)->format('H:i') : '';

            // 休憩・合計の計算はあなたの既存ロジックに合わせて調整
            $breakMin = 0;  // TODO: breakTimes から算出
            $workMin  = ($att?->clock_in && $att?->clock_out)
                        ? Carbon::parse($att->clock_in)->diffInMinutes(Carbon::parse($att->clock_out)) - $breakMin
                        : null;

            $list[] = [
                'id'        => $user->id,
                'name'      => $user->name,
                'clock_in'  => $clockIn,
                'clock_out' => $clockOut,
                'break_min' => $breakMin ?: null,
                'work_min'  => $workMin,
                // 各日の詳細（当日×ユーザー）へ飛ぶリンク
                'detail_url'=> route('admin.attendances.show', ['date' => $date, 'id' => $user->id]),
            ];
        }

        // 既存の管理者用一覧Bladeに合わせて値を渡す
        return view('admin.attendance.index', [
            'title'    => $base->isoFormat('YYYY/MM'),
            'date'     => $base->toDateString(),           // ナビ用に基準日
            'prevDate' => $base->copy()->subMonth()->toDateString(),
            'nextDate' => $base->copy()->addMonth()->toDateString(),
            'list'     => $list,
        ]);
    }
}

