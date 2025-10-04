<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StampCorrectionRequest;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminRequestController extends Controller
{
    // 一覧（?status=pending|approved）
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');

        $list = StampCorrectionRequest::with(['user','attendance'])
            ->when(in_array($status, ['pending','approved']), fn($q) => $q->where('status', $status))
            ->latest('created_at')
            ->get();

        return view('admin.requests.index', compact('status','list'));
    }

    // 詳細
    public function show(int $id)
    {
        $req = StampCorrectionRequest::with(['user','attendance'])->findOrFail($id);
        return view('admin.requests.show', compact('req'));
    }

    // 承認（★ここが本体）
    public function approve(int $id, Request $request)
    {
        $req = StampCorrectionRequest::lockForUpdate()->findOrFail($id);
        if ($req->status !== 'pending') {
            return back()->withErrors(['status' => 'この申請は処理済みです。']);
        }

        DB::transaction(function () use ($req) {
            // 勤怠を確定/作成
            $attendance = Attendance::firstOrCreate(
                ['id' => $req->attendance_id],
                ['user_id' => $req->user_id, 'work_date' => Carbon::parse($req->requested_clock_in ?? $req->requested_clock_out ?? now())->toDateString()]
            );

            // 出退勤
            if ($req->requested_clock_in)  $attendance->clock_in  = $req->requested_clock_in;
            if ($req->requested_clock_out) $attendance->clock_out = $req->requested_clock_out;

            // 休憩（配列なら回すが、今回は単純に 0..2 本を想定）
            $attendance->breakTimes()->delete();
            foreach ([$req->requested_break_start, $req->requested_break_end] as $idx => $val) {
                // 2本目対応するなら別カラムに合わせて増やす
            }
            // NOTE: 必要ならここで $attendance->breakTimes()->create([...]) を実装

            $attendance->status = $attendance->clock_out ? 'closed' : 'working';
            $attendance->save();

            // 申請を承認へ
            $req->update([
                'status'     => 'approved',
                'reviewed_by'=> Auth::id(),
                'reviewed_at'=> now(),
            ]);
        });

        return redirect()->route('admin.requests.show', ['id'=>$id])->with('success','申請を承認しました。');
    }
}