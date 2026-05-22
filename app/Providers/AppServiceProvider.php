<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrap();

        // Boss role bypasses every permission check — must run before Spatie's gate callback.
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('boss')) {
                return true;
            }
        });

        Gate::define('boss', fn ($user) => $user->isBoss());
        Gate::define('operator', fn ($user) => $user->isOperator());
    }
}
