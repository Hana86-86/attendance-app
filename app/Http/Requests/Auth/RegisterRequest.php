<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => trim((string) $this->input('email')),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'confirmed', 'min:8'],
            'password_confirmation' => ['required','string','min:8','same:password'],
        ];
    }
    public function messages(): array
    {
        return [
            'name.required'                 => 'お名前を入力してください',
            'email.required'                => 'メールアドレスを入力してください',
            'email.email'                   => 'メールアドレスの形式が正しくありません',
            'password.required'             => 'パスワードを入力してください',
            'password.min'                  => 'パスワードは8文字以上で入力してください',
            'password.confirmed'            => 'パスワードと一致しません',
            'password_confirmation.required'=> 'パスワードを入力してください',
            'password_confirmation.same'    => 'パスワードと一致しません',
        ];
    }
    public function  attributes(): array {
        return [
            'name' => 'お名前',
            'email' => 'メールアドレス',
            'password' => 'パスワード',
            'password_confirmation' => '確認用パスワード',
        ];
    }
}
