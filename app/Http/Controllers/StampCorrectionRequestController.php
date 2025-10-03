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
        return DB::transaction(function () use ($date, $request) {

            // 同日の未処理があるならリジェクト（重複提出ガード）
            $exists = \App\Models\StampCorrectionRequest::where('user_id', \Auth::id())
            ->where('status', 'pending')
            ->whereDate('requested_clock_in', $date)   //日付一致を見る
            ->exists();

            if ($exists) {
                return back()->withErrors(['status' => '同日の承認待ち申請が既に存在します。']);
            }
              // 当日の勤怠行（あれば紐付け）
            $attendance = \App\Models\Attendance::where('user_id', \Auth::id())
            ->where('work_date', $date)
            ->first();

            // 入力値（null 安全＆空行除去）
            $breaks = collect($request->input('breaks', []))
                ->map(fn($b) => [
                    'start' => trim((string)($b['start'] ?? '')),
                    'end'   => trim((string)($b['end']   ?? '')),
                ])
                ->filter(fn($b) => $b['start'] !== '' || $b['end'] !== '')
                ->values()
                ->all();

            $b1 = $breaks[0] ?? ['start' => null, 'end' => null];

            StampCorrectionRequest::create([
                'user_id'               => Auth::id(),
                'attendance_id'         => optional($attendance)->id,
                'requested_clock_in'    => $request->input('clock_in'), // 'HH:MM' or null
                'requested_clock_out'   => $request->input('clock_out'),
                'requested_break_start' => $b1['start'] ?: null,
                'requested_break_end'   => $b1['end'] ?:null,
                'reason'                => $request->input('note'),
                'status'                => 'pending',
            ]);

            return back()->with('status', '修正申請を送信しました。承認結果をお待ちください。');
        });
    }

}