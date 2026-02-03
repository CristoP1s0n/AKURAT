<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\CheckQuarterLock;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'active' => \App\Http\Middleware\ActiveUser::class,
            'lock' => \App\Http\Middleware\CheckQuarterLock::class,
        ]);
    })
    
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
