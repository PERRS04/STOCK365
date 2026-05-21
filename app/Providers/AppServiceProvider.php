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

        // Spatie's HasRoles registers a 'before' gate callback that handles
        // permission checks via can(). The legacy gates below are kept only
        // for the isBoss()/isOperator() shorthand calls in legacy code paths.
        Gate::define('boss', fn ($user) => $user->isBoss());
        Gate::define('operator', fn ($user) => $user->isOperator());
    }
}
