<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\NormalizeAmsDateInputs::class,
            \App\Http\Middleware\SetCurrentOrganization::class,
        ]);

        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo(function () {
            return auth()->user()?->isPlatformAdmin() ? '/platform' : '/get-started';
        });

        $middleware->alias([
            'permission' => \App\Http\Middleware\CheckModulePermission::class,
            'platform' => \App\Http\Middleware\EnsurePlatformAdmin::class,
            'org-active' => \App\Http\Middleware\EnsureOrganizationActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
