<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\StampCorrectionRequest;
use App\Http\Requests\Attendance\UpdateRequest;
use App\Models\Attendance;
use Carbon\Carbon;
use App\Http\Controllers\Concerns\PacksAttendance;

class StampCorrectionRequestController extends Controller
{
    use PacksAttendance;

    public function index(Request $request)
    {
        $status = in_array($request->query('status'), ['pending', 'approved'])
                ? $request->query('status')
                : 'pending';

        $list = StampCorrectionRequest::where('user_id', Auth::id())
                ->where('status', $status)
                ->latest('created_at')
                ->get();

        $detail     = null;
        $detailVars = [];
        if ($id = $request->query('id')) {
            $detail = StampCorrectionRequest::with(['attendance','user'])->where('user_id', Auth::id())->find($id);

            if ($detail) {
                $baseDate = optional($detail->attendance?->work_date)?->toDateString()
                            ?? Carbon::parse($detail->requested_clock_in ?? $detail->requested_clock_out ?? now())->toDateString();

                // スタッフは承認待ちなら編集不可・赤メッセージ、承認済みならボタンは「承認済み」
                $ui = [
                    'role'    => 'staff',
                    'status'  => $detail->status === 'pending' ? 'pending' : 'approved',
                    'canEdit' => false,
                    'footer'  => $detail->status === 'pending' ? 'message' : 'approved',
                    'form'    => null,
                ];

                $detailVars = $this->packDetail($detail->attendance, $detail->user, $baseDate, $ui);
            }
        }

        return view('requests.list', [
            'status'     => $status,
            'list'       => $list,
            'detail'     => $detail,
            'detailVars' => $detailVars,
        ]);
    }


    public function store(UpdateRequest $request)
    {
        $data  = $request->validated();

        $inputDate = $request->input('date');

        $workDate = Carbon::parse($inputDate)->toDateString();

        $breaks = $data['breaks'] ?? [];

        $dt = function (?string $t) use ($workDate) {
            return $t ? Carbon::createFromFormat('Y-m-d H:i', "{$workDate} {$t}") : null;
        };

        $attendance = Attendance::where('user_id', Auth::id())
            ->whereDate('work_date', $workDate)
            ->first();

        return DB::transaction(function () use ($workDate, $data, $breaks, $attendance, $dt) {

            $exists = StampCorrectionRequest::where('user_id', Auth::id())
                ->when(
                    $attendance,
                    fn ($q) => $q->where('attendance_id', $attendance->id),
                    fn ($q) => $q->whereDate('requested_clock_in', $workDate)
                )
                ->where('status', 'pending')
                ->exists();

            if ($exists) {
                return back()
                    ->withErrors(['status' => '同日の申請が未承認のまま既に存在します。'])
                    ->withInput();
            }

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
                'requested_clock_in'  => $dt($data['clock_in']  ?? null),
                'requested_clock_out' => $dt($data['clock_out'] ?? null),
                'requested_break'     => $requested_break,
                'reason'              => $data['reason'] ?? '修正申請',
                'status'              => 'pending',
            ]);

            return back()->with('success', '修正申請を送信しました。');
        });
    }
}