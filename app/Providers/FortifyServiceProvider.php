<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use App\Actions\Fortify\CreateNewUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Auth\LoginRequest as UserLoginRequest;

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

        // スタッフ（role=user）ログイン
        Fortify::authenticateUsing(function (Request $request) {
            // FortifyのRequest → 自作FormRequestへ“中身ごと”コピー
            /** @var \App\Http\Requests\Auth\LoginRequest $form */
            $form = UserLoginRequest::createFrom($request); // 入力値を持ったまま生成
            $form->setContainer(app())->setRedirector(app('redirect'));

            $form->validateResolved();

            $data = $form->validated();

            // スタッフ(role='user')だけを対象に認証
            $user = User::where('email', $data['email'] ?? null)
                        ->where('role', 'user')
                        ->first();

            if ($user && Hash::check($data['password'] ?? '', $user->password)) {
                // webガード
                return $user;
            }

            return null;
        });
    }
}
