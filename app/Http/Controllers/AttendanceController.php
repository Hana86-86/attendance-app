<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\Attendance\{ClockInRequest,ClockOutRequest,BreakInRequest,BreakOutRequest};
use App\Models\{Attendance,BreakTime};
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    // 出勤画面：状態でボタン切替
    public function create() {
        $today = now()->toDateString();
        $attendance = Attendance::with('breakTimes')
            ->where('user_id',Auth::id())
            ->where('work_date',$today)
            ->first();
        return view('attendance.create',compact('attendance','today'));
    }
    // 出勤処理
    public function clockIn(ClockInRequest $request) {
        Attendance::create([
            'user_id' => Auth::id(),
            'work_date' => now()->toDateString(),
            'clock_in' => now(),
            'status' => 'working',
        ]);
        return back()->with('success','出勤しました');
    }
    // 休憩入り処理
    public function breakIn(BreakInRequest $request) {
        $attendance = Attendance::where('user_id',Auth::id())
            ->where('work_date',now()->toDateString())->firstOrFail();
        $attendance->breakTimes()->create(['start'=>now()]);
        return back()->with('success','休憩に入りました');
    }
    // 休憩戻り処理
    public function breakOut(BreakOutRequest $request) {
        $attendance = Attendance::where('user_id',Auth::id())
            ->where('work_date',now()->toDateString())->firstOrFail();
        $open = $attendance->breakTimes()->whereNull('end')->firstOrFail();
        $open->update(['end'=>now()]);
        return back()->with('success','休憩から戻りました');
    }
    // 退勤処理
    public function clockOut(ClockOutRequest $request) {
        $attendance = Attendance::where('user_id',Auth::id())
            ->where('work_date',now()->toDateString())->firstOrFail();
        $attendance->update([
            'clock_out' => now(),
            'status' => 'closed',
        ]);
        return back()->with('success','お疲れ様でした。');
    }
}
