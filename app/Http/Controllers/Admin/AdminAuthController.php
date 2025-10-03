<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Admin\Auth\AdminLoginRequest;

class AdminAuthController extends Controller
{
    public function showLoginForm(Request $request)
    {
        // 過去の intended を消す（古い遷移先へ飛ばされないように）
        $request->session()->forget('url.intended');

        if (Auth::check()) {
            // 非管理者でログイン中なら一旦ログアウトしてフォーム表示
            if ((Auth::user()->role ?? 'user') !== 'admin') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            } else {
                // 既に管理者でログイン中なら管理TOPへ
                return redirect()->route('admin.attendances.index', [
                    'date' => now()->toDateString(),
                ]);
            }
        }

        return view('admin.auth.login');
    }

    public function login(AdminLoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        $remember = (bool) $request->boolean('remember');

        // 資格情報で認証
        if (! Auth::attempt($credentials, $remember)) {
            // 認証失敗時は「ログイン情報が登録されていません」を返す
            return back()
                ->withErrors(['email' => 'ログイン情報が登録されていません'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        if (Auth::user()->role !== 'admin') {
            // ロール不一致なら強制ログアウト
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors(['email' => '管理者権限がありません。'])
                ->onlyInput('email');
        }


        return redirect()->route('admin.attendances.index', [
            'date' => now()->toDateString(),
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }
}