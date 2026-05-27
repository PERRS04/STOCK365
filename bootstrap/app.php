<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Ensure all HTML responses carry the explicit UTF-8 charset header
        $middleware->web(\App\Http\Middleware\EnforceUtf8::class);

        $middleware->alias([
            'boss'          => \App\Http\Middleware\IsBoss::class,
            'operator'      => \App\Http\Middleware\IsOperator::class,
            'cash.session'  => \App\Http\Middleware\EnsureCashSessionOpen::class,
            'op.guard'      => \App\Http\Middleware\OperationalIntegrityGuard::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
