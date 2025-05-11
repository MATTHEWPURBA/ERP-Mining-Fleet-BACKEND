<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\Paginator;
use App\Services\BookingService;
use App\Services\BookingApprovalService;
use App\Services\DashboardService;
use App\Services\ReportService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register implemented services
        $this->app->singleton(BookingService::class);
        $this->app->singleton(BookingApprovalService::class);
        $this->app->singleton(DashboardService::class);
        $this->app->singleton(ReportService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set default string length for MySQL
        Schema::defaultStringLength(191);
        
        // Configure pagination to use Bootstrap
        Paginator::useBootstrap();
    }
}