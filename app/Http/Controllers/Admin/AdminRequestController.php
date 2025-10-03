<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveRequest;
use App\Models\StampCorrectionRequest;
use App\Models\Attendance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AdminRequestController extends Controller
{
    public function approve(int $id, ApproveRequest $request)
    {
        DB::transaction(function () use ($id) {
            $req = StampCorrectionRequest::lockForUpdate()->findOrFail($id);
            if ($req->status !== 'pending') {
                abort(400, 'この申請は承認待ちではありません。');
            }

            // 対象勤怠（無ければ作成でも可）
            $attendance = Attendance::firstOrCreate(
                ['id' => $req->attendance_id],
                // attendance_id が null の場合のフォールバック作成例（必要ならコメントアウト解除）
                // ['user_id' => $req->user_id, 'work_date' => Carbon::parse($req->requested_clock_in)->toDateString()]
            );

            // 勤怠へ反映（必要な列名に合わせて調整）
            if (!is_null($req->requested_clock_in)) {
                $attendance->clock_in = $req->requested_clock_in;
            }
            if (!is_null($req->requested_clock_out)) {
                $attendance->clock_out = $req->requested_clock_out;
            }
            // 休憩1本想定
            if (!is_null($req->requested_break_start) || !is_null($req->requested_break_end)) {
                // ここはあなたの休憩保存仕様に合わせて実装（breakTimesテーブル等）
            }
            $attendance->save();

            // 申請を承認済みに
            $req->update([
                'status'      => 'approved',
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);
        });

        return back()->with('success', '申請を承認しました。');
    }
}
