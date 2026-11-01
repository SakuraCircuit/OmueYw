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
        // 信任所有代理
        $middleware->trustProxies("*");
        $middleware->remove(\Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class);
        $middleware->alias([
            "IsInstall" => \App\Http\Middleware\IsInstall::class,
            "IdentifierFilter" => \App\Http\Middleware\IdentifierFilter::class,
            "PassFilter" => \App\Http\Middleware\PassFilter::class,
            "CheckRand" => \App\Http\Middleware\CheckRand::class,
            "AutoUpdate" => \App\Http\Middleware\AutoUpdate::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
