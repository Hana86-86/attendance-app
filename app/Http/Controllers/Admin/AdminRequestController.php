<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\PacksAttendance;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
    $attendance = $detail->attendance; // 実データ
    $baseDate = optional($attendance?->work_date)?->toDateString()
              ?? Carbon::parse($detail->requested_clock_in ?? $detail->requested_clock_out ?? now())->toDateString();

    $ui = [
        'role'    => 'admin',
        'status'  => $detail->status === 'pending' ? 'pending' : 'approved',
        'canEdit' => false,
        'footer'  => $detail->status === 'pending' ? 'approve' : 'approved',
        'form'    => $detail->status === 'pending'
                        ? ['action' => route('admin.requests.approve'),'method' => 'post']
                        : null,
    ];

    // ★ 申請内容を画面用の勤怠に合成（pending のときだけ上書き）
    $attForView = $this->overlayAttendanceWithRequest(
        $attendance,
        $detail->status === 'pending' ? $detail : null,
        $detail->user_id,
        $baseDate
    );

    $detailVars = $this->packDetail($attForView, $detail->user, $baseDate, $ui);
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

    DB::transaction(function () use ($id) {
        // ★ トランザクション内で行ロック
        $req = StampCorrectionRequest::with('attendance')->whereKey($id)->lockForUpdate()->firstOrFail();

        if ($req->status !== 'pending') {
            return; // 何もしない
        }

        // ★ 勤怠は user_id+work_date で取得/作成（id指定はやめる）
        $workDate = Carbon::parse($req->requested_clock_in ?? $req->requested_clock_out ?? now())->toDateString();

        $attendance = $req->attendance
            ?: Attendance::firstOrCreate(['user_id' => $req->user_id, 'work_date' => $workDate]);

        // 出退勤（あるものだけ上書き）
        if ($req->requested_clock_in)  $attendance->clock_in  = $req->requested_clock_in;
        if ($req->requested_clock_out) $attendance->clock_out = $req->requested_clock_out;

        // 備考
        if (!empty($req->reason)) $attendance->reason = $req->reason;

        // 休憩：全削除→申請の配列を反映
        $attendance->breakTimes()->delete();
        foreach (($req->requested_break ?? []) as $b) {
            $attendance->breakTimes()->create([
                'start' => $b['start'] ?? null,
                'end'   => $b['end']   ?? null,
            ]);
        }

        $attendance->status = $attendance->clock_out ? 'closed' : 'working';
        $attendance->save();

        // ★ リクエスト側も承認 & 紐付け更新
        $req->update([
            'status'        => 'approved',
            'attendance_id' => $attendance->id,
        ]);
    });

    return redirect($backUrl ?: route('admin.requests.index', [
        'status' => 'approved',
        'id'     => $id, // 承認後に同じ詳細を開いたまま「承認済み」表示に切替
    ]))->with('success', '承認しました。');
}
}