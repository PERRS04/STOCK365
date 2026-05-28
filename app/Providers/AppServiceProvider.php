<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
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
        // Enforce UTF-8 for all mbstring operations across the entire application
        mb_internal_encoding('UTF-8');
        mb_regex_encoding('UTF-8');

        // Use the STOCK365 premium pagination component (resources/views/vendor/pagination/tailwind.blade.php)
        Paginator::defaultView('vendor.pagination.tailwind');
        Paginator::defaultSimpleView('vendor.pagination.tailwind');

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
