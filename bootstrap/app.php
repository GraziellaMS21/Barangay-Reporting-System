<?php

use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\EnsureAgencyActive;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\SetupCompleted;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/admin');

        $middleware->web(append: [
            SetupCompleted::class,
        ]);

        $middleware->web(append: [
            SetupCompleted::class,
        ]);

        $middleware->alias([
            'role' => EnsureRole::class,
            'setup.complete' => SetupCompleted::class,
            'permission' => CheckPermission::class,
            'password.changed' => EnsurePasswordChanged::class,
            'agency.active' => EnsureAgencyActive::class,
            'setup.complete' => SetupCompleted::class,
            'permission' => CheckPermission::class,
            'password.changed' => EnsurePasswordChanged::class,
            'agency.active' => EnsureAgencyActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
