<?php
namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        // 未認証なら誘導へ
        if (! $request->user()->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        // 認証済：ロールで分ける
        if ($request->user()->role === 'admin') {
            return redirect()->intended('/admin/attendances');
        }
        return redirect()->intended('/attendance');
    }
}
