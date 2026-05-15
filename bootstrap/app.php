<?php

use App\Http\Middleware\CheckRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Archivo de rutas independiente para auth + roles
            // (no tocamos web.php)
            Route::middleware('web')
                ->group(base_path('routes/auth_roles.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Alias 'role' apunta al middleware CheckRole
        // Uso: Route::middleware('role:admin')  o  Route::middleware('role:receptionist')
        $middleware->alias([
            'role' => CheckRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
