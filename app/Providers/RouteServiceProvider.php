<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
       /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        // Define rate limiting for API routes
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Register route groups
        $this->routes(function () {
            // API routes with API middleware group
            Route::middleware('api')
                ->prefix('api') // This ensures the prefix is correctly applied
                ->group(base_path('routes/api.php'));

            // Web routes with web middleware group
            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}


// This file is part of the Laravel framework.
// app/Providers/RouteServiceProvider.php