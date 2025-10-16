<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\PacksAttendance;
use App\Http\Controllers\Controller;
use App\Models\StampCorrectionRequest;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminRequestController extends Controller
{
    use PacksAttendance;

    public function index(Request $request)
    {
        $status   = in_array($request->get('status'), ['pending','approved']) ? $request->get('status') : 'pending';
        $detailId = $request->get('id');

        $list = StampCorrectionRequest::with(['user','attendance'])
                    ->when($status, fn($q) => $q->where('status',$status))
                    ->orderByDesc('id')
                    ->get();

        $detail     = null;
        $detailVars = [];

        if ($detailId) {
            $detail = StampCorrectionRequest::with(['user','attendance.breakTimes'])->find($detailId);
            if ($detail) {
                $attendance = $detail->attendance;

                $baseDate = optional($attendance?->work_date)?->toDateString()
                            ?? Carbon::parse($detail->requested_clock_in ?? $detail->requested_clock_out ?? now())->toDateString();

                $ui = [
                    'role'    => 'admin',
                    'status'  => $detail->status === 'pending' ? 'pending' : 'approved',
                    'canEdit' => false,
                    'footer'  => $detail->status === 'pending' ? 'approve' : 'approved',
                    'form'    => $detail->status === 'pending'
                                    ? [
                                        'action' => route('admin.requests.approve'),
                                        'method' => 'post',
                                    ]
                                    : null,
                ];

                $detailVars = $this->packDetail($attendance, $detail->user, $baseDate, $ui);

                $detailVars['detailId'] = $detail->id;
            }
        }

        return view('admin.requests.index', compact('status','list','detail','detailVars'));
    }
    // 承認（同じURLに戻ってボタンが「承認済み」に変わる）
public function approve(Request $request)
{
    $id      = (int)$request->input('id');
    $backUrl = (string)$request->input('redirect', '');

    $req = StampCorrectionRequest::lockForUpdate()->with(['attendance'])
            ->findOrFail($id);

    if ($req->status !== 'pending') {
        return redirect($backUrl ?: route('admin.requests.index', ['status' => 'approved']))
            ->withErrors(['status' => 'この申請は処理済みです。']);
    }

    //  反映処理 ----
    DB::transaction(function () use ($req) {

        // 勤怠を取得/作成
        $attendance = Attendance::firstOrCreate(
            ['id' => $req->attendance_id],
            [
                'user_id'  => $req->user_id,
                'work_date' => Carbon::parse($req->requested_clock_in ?? $req->requested_clock_out ?? now())
                                    ->toDateString(),
            ]
        );

        // 出退勤の反映（存在するものだけ上書き）
        if ($req->requested_clock_in)  $attendance->clock_in  = $req->requested_clock_in;
        if ($req->requested_clock_out) $attendance->clock_out = $req->requested_clock_out;

        // 休憩の反映
        $attendance->breakTimes()->delete();
        foreach (($req->requested_break ?? []) as $idx => $val) {

            $attendance->breakTimes()->create([
                'start' => $val['start'] ?? null,
                'end'   => $val['end']   ?? null,
            ]);
        }

        // 勤怠ステータス
        $attendance->status = $attendance->clock_out ? 'closed' : 'working';
        $attendance->save();

        // 申請を承認済みに更新
        $req->update([
            'status'      => 'approved',
        ]);
    });

    //  同じURLへ戻す
    return redirect($backUrl ?: route('admin.requests.index', ['status' => 'approved', 'id' => $id]))
        ->with('success', '承認しました。');
}
}