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

];