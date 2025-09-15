<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\VerifyCsrfToken as AppVerifyCsrfToken;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // 
        $middleware->group('admin', [
            \App\Http\Middleware\AdminMiddleware::class,
        ]);
        
        $middleware->group('enforcer', [
            \App\Http\Middleware\EnforcerMiddleware::class,
        ]);
        $middleware->group('violator', [
            \App\Http\Middleware\ViolatorMiddleware::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
