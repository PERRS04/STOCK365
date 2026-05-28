<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// ── .env.local override loader ────────────────────────────────────────────────
// Loaded BEFORE LoadEnvironmentVariables bootstrapper so these values win.
// DotEnv uses immutable loading — it won't overwrite vars already in $_ENV.
// Create .env.local (gitignored) or run: php artisan stock365:local
$_envLocalPath = dirname(__DIR__) . '/.env.local';
if (file_exists($_envLocalPath)) {
    foreach (file($_envLocalPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $_line) {
        $_line = trim($_line);
        if (str_starts_with($_line, '#') || !str_contains($_line, '=')) {
            continue;
        }
        [$_key, $_val] = explode('=', $_line, 2);
        $_key = trim($_key);
        $_val = trim(trim($_val), '"\'');
        putenv("{$_key}={$_val}");
        $_ENV[$_key]    = $_val;
        $_SERVER[$_key] = $_val;
    }
    unset($_envLocalPath, $_line, $_key, $_val);
}
unset($_envLocalPath);

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
