<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',  // Add this line if missing
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )



->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'cors' => \App\Http\Middleware\Cors::class,
    ]);
    
    // You can also add it to the global HTTP middleware stack
    $middleware->append(\App\Http\Middleware\Cors::class);
})


->withProviders([
    // Other providers...
    Laravel\Sanctum\SanctumServiceProvider::class,
])

    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

