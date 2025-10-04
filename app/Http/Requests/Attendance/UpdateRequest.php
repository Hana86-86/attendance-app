<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [

            'clock_in'  => ['nullable', 'date_format:H:i', 'before_or_equal:clock_out'],
            'clock_out' => ['nullable', 'date_format:H:i', 'after_or_equal:clock_in'],

            'breaks'         => ['array'],
            'breaks.*.start' => ['nullable', 'date_format:H:i'],
            'breaks.*.end'   => ['nullable', 'date_format:H:i'],

            'note'           => ['required', 'string', 'max:500'],

        ];
    }
    public function messages(): array
    {
        return [
            'clock_in.before_or_equal'  => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_out.after_or_equal'  => '出勤時間もしくは退勤時間が不適切な値です',
            'breaks.*.start.date_format' => '休憩時間が不適切な値です',
            'breaks.*.end.date_format'   => '休憩時間が不適切な値です',
            'note.required'              => '備考を記入してください',
        ];
    }
    public function withValidator($validator)
{
    $validator->after(function ($v) {
        $in  = $this->input('clock_in');
        $out = $this->input('clock_out');
        $breaks = $this->input('breaks', []);

        $in  = $in  ? Carbon::createFromFormat('H:i', $in)  : null;
        $out = $out ? Carbon::createFromFormat('H:i', $out) : null;

            // 休憩開始は出勤前NG／退勤後NG」
            foreach ($breaks as $i => $b) {
                if (!empty($b['start'])) {
                    $sc = Carbon::createFromFormat('H:i', $b['start']);
                    if ($in  && $sc->lt($in))  {
                        $v->errors()->add("breaks.$i.start", '休憩時間が不適切な値です');
                    }
                    if ($out && $sc->gt($out)) {
                        $v->errors()->add("breaks.$i.start", '休憩時間が不適切な値です');
                    }
                }

                // 休憩終了は退勤後NG
                if (!empty($b['end'])) {
                    $ec = Carbon::createFromFormat('H:i', $b['end']);
                    if ($out && $ec->gt($out)) {
                        $v->errors()->add("breaks.$i.end", '休憩時間もしくは退勤時間が不適切な値です');
                    }
                }
            }
        });
    }
}