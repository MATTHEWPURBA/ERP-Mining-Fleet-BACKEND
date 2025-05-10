<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Vehicle;
use App\Models\User;
use App\Policies\BookingPolicy;
use App\Policies\VehiclePolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Booking::class => BookingPolicy::class,
        Vehicle::class => VehiclePolicy::class,
        User::class => UserPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Define gates for various user roles
        Gate::define('admin-access', function (User $user) {
            return $user->role === 'Administrator';
        });
        
        Gate::define('approver-access', function (User $user) {
            return in_array($user->role, ['Administrator', 'Approver']);
        });
    }
}
