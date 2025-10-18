<?php

return [


    'required' => ':attribute を入力してください。',
    'email'    => '正しいメールアドレス形式で入力してください。',
    'min'      => [
        'string' => ':attribute は :min 文字以上で入力してください。',
    ],
    'confirmed' => ':attribute が一致しません。',
    'unique'    => 'この :attribute は既に使用されています。',



    'custom' => [
        'email' => [
            'required' => 'メールアドレスを入力してください。',
            'email'    => 'メールアドレスの形式で入力してください。',
            'unique'   => 'このメールアドレスは既に登録されています。',
        ],
        'password' => [
            'required'  => 'パスワードを入力してください。',
            'min'       => 'パスワードは8文字以上で入力してください。',
            'confirmed' => '確認用のパスワードと一致しません。',
        ],
        'password_confirmation' => [
            'same' => 'パスワードと一致しません'
        ],
        'name' => [
            'required' => 'お名前を入力してください。',
        ],
    ],


    'attributes' => [
        'name'     => 'お名前',
        'email'    => 'メールアドレス',
        'password' => 'パスワード',
    ],

    'attendance' => [
        'common' => [
            'clock_out_invalid'   => '出勤時間もしくは退勤時間が不適切な値です',
            'break_start_invalid' => '休憩時間が不適切な値です',
            'break_end_invalid'   => '休憩時間もしくは退勤時間が不適切な値です',
            'reason_required'     => '備考を記入してください',
        ],
    ],

    // フィールド名の表示
    'attributes' => [
        'name'     => 'お名前',
        'email'    => 'メールアドレス',
        'password' => 'パスワード',

        // 一般ユーザーの申請フォーム
        'requested_clock_in'     => '出勤時間',
        'requested_clock_out'    => '退勤時間',
        'requested_break_start'  => '休憩開始時間',
        'requested_break_end'    => '休憩終了時間',
        'reason'                 => '備考',

        // 管理者の編集フォーム
        'clock_in'               => '出勤時間',
        'clock_out'              => '退勤時間',
        'breaks.*.start'         => '休憩開始時間',
        'breaks.*.end'           => '休憩終了時間',
    ],
];
