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
    }
}