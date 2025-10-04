<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\StampCorrectionRequest;
use App\Http\Requests\Attendance\UpdateRequest;
use Carbon\Carbon;

class StampCorrectionRequestController extends Controller
{
    // 一覧（承認待ち／承認済みを status で出し分け）
    public function index(?string $status = 'pending')
    {
        $status = in_array($status, ['pending','approved']) ? $status : 'pending';

        $list = StampCorrectionRequest::where('user_id', Auth::id())
            ->where('status', $status)
            ->latest('created_at')
            ->get();

        // 共通の一覧ビューを使用
        return view('attendance.stamp_collection.list', [
            'status' => $status,
            'list'   => $list,
        ]);
    }

    // 登録（勤怠詳細からPOST）※FormRequestでバリデーション
    public function store(string $date, UpdateRequest $request)
    {
        $data   = $request->validated();
        $breaks = $data['breaks'] ?? [];

        $attendance = Attendance::where('user_id', Auth::id())
        ->whereDate('work_date', $date)
        ->first();

        return DB::transaction(function () use ($date, $data, $breaks, $attendance) {

            // 二重申請のガード
            $exists = StampCorrectionRequest::where('user_id', Auth::id())
                ->when($attendance,
                fn ($q) => $q->where('attendance_id', $attendance->id),
                fn ($q) => $q->whereDate('requested_clock_in', $date)
            )
            ->exists();

            if ($exists) {
            return back()->withErrors(['status' => '同日の申請が未承認のまま既に存在します。']);
    }

            // 既存の当日勤怠
            $attendance = Attendance::where('user_id', Auth::id())
                            ->whereDate('work_date', date())
                            ->first();

            // 申請作成
            StampCorrectionRequest::create([
                'user_id'               => Auth::id(),
                'attendance_id'         => optional($attendance)->id,
                'requested_clock_in'    => $data['clock_in']   ?? null,
                'requested_clock_out'   => $data['clock_out']  ?? null,
                'requested_break_start' => $breaks[0]['start'] ?? null,
                'requested_break_end'   => $breaks[0]['end']   ?? null,
                'note'                  => $data['note'],
                'status'                => 'pending',
            ]);

            return back()->with('success', '修正申請を送信しました。');
        });
    }
    public function show(int $id)
{
    $req = StampCorrectionRequest::with(['attendance','user'])->findOrFail($id);
    return view('attendance.stamp_collection.list', [
        'status' => $req->status ?? 'pending',
        'list'   => collect([$req]),
]);
}
}