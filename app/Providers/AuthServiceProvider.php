<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;


class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerPolicies();

        // 管理者専用 Gate
        Gate::define('admin-only', fn(User $u) => $u->isAdmin());

        // 勤怠データ閲覧用
        Gate::define('view-attendance-of-user', function (User $authUser, User $targetUser) {
            return $authUser->isAdmin() || $authUser->id === $targetUser->id;
        });
    }
}