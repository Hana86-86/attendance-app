<?php

namespace App\Http\Requests\StampCorrection;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreRequest extends FormRequest
{

    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {

        $inputDate = $this->input('date');
        $workDate  = $inputDate ? Carbon::parse($inputDate)->toDateString() : null;

        $clockIn  = $this->input('clock_in')  ?? $this->input('clockIn');
        $clockOut = $this->input('clock_out') ?? $this->input('clockOut');

        $breaks = $this->input('breaks', []);
        $b1s = $this->input('break1_start');  $b1e = $this->input('break1_end');
        $b2s = $this->input('break2_start');  $b2e = $this->input('break2_end');
        if ($b1s !== null || $b1e !== null) {
            $breaks[0]['start'] = $breaks[0]['start'] ?? $b1s;
            $breaks[0]['end']   = $breaks[0]['end']   ?? $b1e;
        }
        if ($b2s !== null || $b2e !== null) {
            $breaks[1]['start'] = $breaks[1]['start'] ?? $b2s;
            $breaks[1]['end']   = $breaks[1]['end']   ?? $b2e;
        }

        $attendanceId = null;
        if ($workDate) {
            $attendanceId = Attendance::where('user_id', Auth::id())
                ->whereDate('work_date', $workDate)
                ->value('id'); // 1件のidだけ取得
        }


        $this->merge([
            'date'          => $workDate,
            'clock_in'      => $clockIn,
            'clock_out'     => $clockOut,
            'breaks'        => $breaks,
            'attendance_id' => $attendanceId,
        ]);
    }

    public function rules(): array
    {
        return [
            // 勤務日（Y-m-d）
            'date'              => ['required', 'date_format:Y-m-d'],

            // 出退勤（H:i）
            'clock_in'          => ['nullable', 'date_format:H:i'],
            'clock_out'         => ['nullable', 'date_format:H:i'],

            // 休憩（H:i）
            'breaks'            => ['nullable', 'array'],
            'breaks.*.start'    => ['nullable', 'date_format:H:i'],
            'breaks.*.end'      => ['nullable', 'date_format:H:i'],

            // 勤怠IDは「存在すればOK」
            'attendance_id'     => ['nullable', 'integer', 'exists:attendances,id'],

            // 備考
            'reason'            => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required'        => '日付を選択してください。',
            'date.date_format'     => '日付の形式が正しくありません。',
            'reason.required'      => __('validation.attendance.common.reason_required'),
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $in  = $this->t($this->input('clock_in'));
            $out = $this->t($this->input('clock_out'));

            // 出勤 < 退勤
            if ($in && $out && $in->gt($out)) {
                $v->errors()->add('clock_out', __('validation.attendance.common.clock_out_invalid'));
            }

            // 休憩の検証
            foreach ((array)$this->input('breaks', []) as $i => $b) {
                // start
                if (!empty($b['start'])) {
                    $bs = $this->t($b['start']);
                    if ($bs) {
                        if ($in  && $bs->lt($in))  {
                            $v->errors()->add("breaks.$i.start", __('validation.attendance.common.break_start_invalid'));
                        }
                        if ($out && $bs->gt($out)) {
                            $v->errors()->add("breaks.$i.start", __('validation.attendance.common.break_start_invalid'));
                        }
                    }
                }
                // end
                if (!empty($b['end'])) {
                    $be = $this->t($b['end']);
                    if ($be) {
                        $bs = !empty($b['start']) ? $this->t($b['start']) : null;
                        if ($bs && $be->lt($bs)) {
                            $v->errors()->add("breaks.$i.end", __('validation.attendance.common.break_end_invalid'));
                        }
                        if ($out && $be->gt($out)) {
                            $v->errors()->add("breaks.$i.end", __('validation.attendance.common.break_end_invalid'));
                        }
                    }
                }
            }
        });
    }

    private function t(?string $value): ?Carbon
    {
        if (!$value) return null;
        try { return Carbon::createFromFormat('H:i', $value); }
        catch (\Throwable) { return null; }
    }
}