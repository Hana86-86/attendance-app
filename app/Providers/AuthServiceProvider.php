<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->registerPolicies();

        // 管理者専用 Gate
        Gate::define('admin-only', fn($user) => ($user?->role ?? 'user') === 'admin');

        // 勤怠データ閲覧用
        Gate::define('view-attendance-of-user', function ($authUser, $targetUser) {
        return $authUser->role === 'admin' || $authUser->id === $targetUser->id;
    });
    }
}