<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'メールアドレスまたはパスワードが正しくありません。'])
                         ->onlyInput('email');
        }

        $request->session()->regenerate();

        if ((Auth::user()->role ?? 'user') !== 'admin') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return back()->withErrors(['email' => '管理者権限がありません。']);
        }

        // 念のため intended を破棄
        $request->session()->forget('url.intended');

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