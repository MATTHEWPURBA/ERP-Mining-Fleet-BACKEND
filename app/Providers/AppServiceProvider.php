<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\Paginator;
use App\Services\BookingService;
use App\Services\VehicleService;
use App\Services\MaintenanceService;
use App\Services\FuelLogService;
use App\Services\ReportService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register essential services
        $this->app->singleton(BookingService::class);
        // $this->app->singleton(VehicleService::class);
        // $this->app->singleton(MaintenanceService::class);
        // $this->app->singleton(FuelLogService::class);
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