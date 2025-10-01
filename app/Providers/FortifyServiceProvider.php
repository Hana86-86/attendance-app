<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use App\Actions\Fortify\CreateNewUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Fortify の Contract を use
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

// 自作レスポンスクラスを use
use App\Http\Responses\RegisterResponse;
use App\Http\Responses\LoginResponse;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(RegisterResponseContract::class, RegisterResponse::class);
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email');
            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });

        // 認証/登録/メール認証 ビュー
        Fortify::loginView(fn () => view('auth.login'));
        Fortify::registerView(fn () => view('auth.register'));

        Fortify::verifyEmailView(fn () => view('auth.verify'));

        Fortify::createUsersUsing(CreateNewUser::class);

         // スタッフ用ログイン（/login の POST）
        Fortify::authenticateUsing(function ($request) {
        $user = User::where('email', $request->input('email'))
                    ->where('role', 'user')     // 「スタッフ」限定
                    ->first();

        if ($user && Hash::check($request->input('password'), $user->password)) {
            return $user; // web ガードでログイン
        }

        return null;
    });
    }
}
