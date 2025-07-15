<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register your middleware alias here
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);

        // If you need to add it to a specific group
        // $middleware->group('web', [
        //     \App\Http\Middleware\RoleMiddleware::class,
        // ]);

        // For API routes
        // $middleware->group('api', [
        //     'throttle:api',
        //     \Illuminate\Routing\Middleware\SubstituteBindings::class,
        // ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
