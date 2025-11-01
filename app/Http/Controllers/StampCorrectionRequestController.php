<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PacksAttendance;
use App\Http\Requests\StampCorrection\StoreRequest;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StampCorrectionRequestController extends Controller
{
    use PacksAttendance;

    public function index(Request $request)
    {
        // ① 権限判定（管理者かどうか）
        $user    = Auth::user();                 // ← ログイン中ユーザー
        $isAdmin = method_exists($user, 'isAdmin') ? $user->isAdmin() : ($user->role === 'admin');
        // ↑ User::isAdmin() があればそれを使用。なければ role==='admin' 判定。

        // ② クエリの status を安全に解釈（未指定は pending）
        $status = in_array($request->query('status'), ['pending', 'approved'], true)
            ? $request->query('status')
            : 'pending';

        // ③ 一覧データ
        $list = StampCorrectionRequest::with(['attendance', 'user'])
            // 管理者でなければ自分の分だけ
            ->when(!$isAdmin, fn($q) => $q->where('user_id', $user->id))
            ->where('status', $status)
            ->latest('created_at')
            ->get();

        // ④ 右側の詳細（?id=... が来たときだけ）
        $detail     = null;
        $detailVars = [];

        if ($id = $request->query('id')) {
            $detail = StampCorrectionRequest::with(['attendance', 'user'])
                ->when(!$isAdmin, fn($q) => $q->where('user_id', $user->id))
                ->find($id);

            if ($detail) {
                // ⑤ どの日付の勤怠としてプレビューするか（勤怠があればwork_date、なければ申請中の時刻から）
                $baseDate = optional(optional($detail->attendance)->work_date)?->toDateString()
                    ?? Carbon::parse($detail->requested_clock_in ?? $detail->requested_clock_out ?? now())->toDateString();

                // ⑥ 画面用フラグ（role/status/footer/canEdit）
                $ui = $isAdmin
                    ? [
                        'role'    => 'admin',                     // 管理者UI
                        'status'  => $detail->status,             // 'pending' or 'approved'
                        'canEdit' => $detail->status === 'pending' ? false : false, // 承認画面では編集しない（承認ボタンのみ）
                        'footer'  => $detail->status === 'pending' ? 'approve' : 'approved',
                        'form'    => null,
                    ]
                    : [
                        'role'    => 'staff',                     // スタッフUI
                        'status'  => $detail->status,             // 'pending' or 'approved'
                        'canEdit' => false,                        // 一覧の詳細は閲覧のみ
                        'footer'  => $detail->status === 'pending' ? 'message' : 'approved',
                        'form'    => null,
                    ];

                // ⑦ 承認待ちなら「申請内容」を重ね合わせてプレビュー、承認済なら実データのみ
                $attForView = $this->overlayAttendanceWithRequest(
                    $detail->attendance,
                    $detail->status === 'pending' ? $detail : null,
                    $detail->user_id,
                    $baseDate
                );

                // ⑧ Bladeに渡すペイロードを作成（packDetail）
                $detailVars = $this->packDetail($attForView, $detail->user, $baseDate, $ui);
            }
        }

        // ⑨ ビューの選択（※パス名は存在するBladeに合わせること！）
        // 管理者: resources/views/admin/requests/list.blade.php
        // スタッフ: resources/views/requests/list.blade.php
        $view = $isAdmin ? 'admin.requests.index' : 'requests.list';

        return view($view, [
            'status'     => $status,
            'list'       => $list,
            'detail'     => $detail,
            'detailVars' => $detailVars,
        ]);
    }


    public function store(StoreRequest $request)
    {
        $data  = $request->validated();

        $inputDate = $request->input('date');

        $workDate = Carbon::parse($inputDate)->toDateString();

        $breaks = $data['breaks'] ?? [];

        $dt = function (?string $t) use ($workDate) {
            return $t ? Carbon::createFromFormat('Y-m-d H:i', "{$workDate} {$t}") : null;
        };

        $clockIn  = $data['clock_in'] ?? null;
        $clockOut = $data['clock_out'] ?? null;
        if (!$clockIn && !$clockOut) {
            $clockIn = Carbon::createFromFormat('Y-m-d H:i', "{$workDate} 00:00");
        }

        $attendance = Attendance::where('user_id', Auth::id())
            ->whereDate('work_date', $workDate)
            ->first();

        return DB::transaction(function () use ($workDate, $data, $breaks, $attendance, $dt, $clockIn, $clockOut) {

            $exists = StampCorrectionRequest::where('user_id', Auth::id())
                ->when(
                    $attendance,
                    fn($q) => $q->where('attendance_id', $attendance->id),
                    fn($q) => $q->whereDate('requested_clock_in', $workDate)
                )
                ->where('status', 'pending')
                ->exists();

            if ($exists) {
                return back()
                    ->withErrors(['status' => '同日の申請が未承認のまま既に存在します。'])
                    ->withInput();
            }
            $dt = function (?string $t) use ($workDate) {
                return $t ? Carbon::createFromFormat('Y-m-d H:i', "{$workDate} {$t}")->format('Y-m-d H:i:s') : null;
            };
            $requested_break = [];
            foreach ([0, 1] as $i) {
                $st = $breaks[$i]['start'] ?? null;
                $ed = $breaks[$i]['end']   ?? null;
                if ($st || $ed) {
                    $requested_break[] = [
                        'start' => $dt($st),
                        'end'   => $dt($ed),
                    ];
                }
            }

            StampCorrectionRequest::create([
                'user_id'             => Auth::id(),
                'attendance_id'       => optional($attendance)->id,
                'requested_clock_in'  => $clockIn ? Carbon::parse($clockIn) : null,
                'requested_clock_out' => $clockOut ? Carbon::parse($clockOut) : null,
                'requested_break'     => $requested_break,
                'reason'              => $data['reason'] ?? '修正申請',
                'status'              => 'pending',
            ]);
            return back()->with('success', '修正申請を送信しました。');
        });
    }
}
