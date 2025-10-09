<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'clock_in'        => ['nullable', 'date_format:H:i'],
            'clock_out'       => ['nullable', 'date_format:H:i'],
            'breaks'          => ['array'],
            'breaks.*.start'  => ['nullable', 'date_format:H:i'],
            'breaks.*.end'    => ['nullable', 'date_format:H:i'],
            'note'            => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'note.required' => '備考を記入してください',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $in  = $this->parseTimeOrNull($this->input('clock_in'));
            $out = $this->parseTimeOrNull($this->input('clock_out'));
            $breaks = $this->input('breaks', []);

            if ($in && $out && $in->gt($out)) {
                $v->errors()->add('clock_in',  '出勤時間もしくは退勤時間が不適切な値です');
                $v->errors()->add('clock_out', '出勤時間もしくは退勤時間が不適切な値です');
            }

            foreach ($breaks as $i => $b) {
                if (!empty($b['start'])) {
                    $start = $this->parseTimeOrNull($b['start']);
                    if ($start) {
                        if ($in  && $start->lt($in))  {
                            $v->errors()->add("breaks.$i.start", '休憩時間が不適切な値です');
                        }
                        if ($out && $start->gt($out)) {
                            $v->errors()->add("breaks.$i.start", '休憩時間が不適切な値です');
                        }
                    }
                }
                if (!empty($b['end'])) {
                    $end = $this->parseTimeOrNull($b['end']);
                    if ($end && $out && $end->gt($out)) {
                        $v->errors()->add("breaks.$i.end", '休憩時間もしくは退勤時間が不適切な値です');
                    }
                }
            }
        });
    }

    protected function getRedirectUrl()
    {
        $date = $this->route('date') ?? $this->input('date');
        if (!$date) {
            $date = now()->toDateString();
        }
        return route('attendance.detail', ['date' => $date]);
    }

    private function parseTimeOrNull(?string $value): ?Carbon
    {
        if (!$value) return null;
        try {
            return Carbon::createFromFormat('H:i', $value);
        } catch (\Throwable $e) {
            return null;
        }
    }
}