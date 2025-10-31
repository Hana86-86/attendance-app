<?php

namespace App\Http\Requests\Attendance;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'clock_in'  => ['nullable', 'date_format:H:i'],
            'clock_out' => ['nullable', 'date_format:H:i'],
            // 休憩時間（複数）
            'breaks'         => ['nullable', 'array'],
            'breaks.*.start' => ['nullable', 'date_format:H:i'],
            'breaks.*.end'   => ['nullable', 'date_format:H:i'],

            // 休憩時間（単一）
            'break_start'    => ['nullable', 'date_format:H:i'],
            'break_end'      => ['nullable', 'date_format:H:i'],

            // 備考
            'reason'         => ['required', 'string', 'max:500'],
        ];
    }


    public function messages(): array
    {
        return [
            // 「備考を記入してください」
            'reason.required' => __('validation.attendance.common.reason_required'),
        ];
    }


    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            // 出退勤
            $in  = $this->parseTimeOrNull($this->input('clock_in'));
            $out = $this->parseTimeOrNull($this->input('clock_out'));

            if ($in && $out && $in->gt($out)) {
                $v->errors()->add('clock_out', __('validation.attendance.common.clock_out_invalid'));
            }

            foreach ((array) $this->input('breaks', []) as $i => $b) {
                // start
                if (!empty($b['start'])) {
                    $start = $this->parseTimeOrNull($b['start']);
                    if ($start) {
                        if ($in  && $start->lt($in))  {
                            $v->errors()->add("breaks.$i.start", __('validation.attendance.common.break_start_invalid'));
                        }
                        if ($out && $start->gt($out)) {
                            $v->errors()->add("breaks.$i.start", __('validation.attendance.common.break_start_invalid'));
                        }
                    }
                }
                // end
                if (!empty($b['end'])) {
                    $end = $this->parseTimeOrNull($b['end']);
                    if ($end) {
                        $start = !empty($b['start']) ? $this->parseTimeOrNull($b['start']) : null;
                        if ($start && $end->lt($start)) {
                            $v->errors()->add("breaks.$i.end", __('validation.attendance.common.break_end_invalid'));
                        }
                        if ($out && $end->gt($out)) {
                            $v->errors()->add("breaks.$i.end", __('validation.attendance.common.break_end_invalid'));
                        }
                    }
                }
            }


            $bs = $this->parseTimeOrNull($this->input('break_start'));
            $be = $this->parseTimeOrNull($this->input('break_end'));

            if ($bs) {
                if ($in  && $bs->lt($in))  {
                    $v->errors()->add('break_start', __('validation.attendance.common.break_start_invalid'));
                }
                if ($out && $bs->gt($out)) {
                    $v->errors()->add('break_start', __('validation.attendance.common.break_start_invalid'));
                }
            }
            if ($be) {
                if ($bs  && $be->lt($bs))  {
                    $v->errors()->add('break_end', __('validation.attendance.common.break_end_invalid'));
                }
                if ($out && $be->gt($out)) {
                    $v->errors()->add('break_end', __('validation.attendance.common.break_end_invalid'));
                }
            }
        });
    }

    protected function getRedirectUrl()
    {
        $date = $this->route('date') ?? $this->input('date');
        if (!$date) $date = now()->toDateString();
        return route('attendance.detail', ['date' => $date]);
    }

    private function parseTimeOrNull(?string $value): ?Carbon
    {
        if (!$value) return null;
        try { return Carbon::createFromFormat('H:i', $value); }
        catch (\Throwable) { return null; }
    }

}