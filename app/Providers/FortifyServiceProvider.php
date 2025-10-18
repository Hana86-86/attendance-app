<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Requests\Auth\LoginRequest as UserLoginRequest;
use App\Http\Responses\LoginResponse;
use App\Http\Responses\RegisterResponse;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

// 自作レスポンスクラスを use
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Fortify;

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
