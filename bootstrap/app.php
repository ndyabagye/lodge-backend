<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // $middleware->api(prepend: [
        //     \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        // ]);

        // Exclude API routes from CSRF verification
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);

        // Register middleware aliases
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'staff' => \App\Http\Middleware\StaffMiddleware::class,
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
        ]);

        // Throttle configuration
        $middleware->throttleApi();

        // Trust proxies (for production behind load balancer)
        $middleware->trustProxies(at: '*');

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
