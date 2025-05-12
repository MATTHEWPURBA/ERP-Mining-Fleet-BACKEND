<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\BookingApproval;
use App\Models\Vehicle;
use App\Models\User;
use App\Policies\BookingPolicy;
use App\Policies\BookingApprovalPolicy;
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
        BookingApproval::class => BookingApprovalPolicy::class, // Register new policy
        Vehicle::class => VehiclePolicy::class,
        User::class => UserPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Register all policies
        $this->registerPolicies();
        
        // Define gates for roles
        Gate::define('admin-access', function (User $user) {
            return $user->role === 'Administrator';
        });
        
        Gate::define('approver-access', function (User $user) {
            return in_array($user->role, ['Administrator', 'Approver']);
        });
        
        // Define gates for common operations
        Gate::define('manage-users', function (User $user) {
            return $user->role === 'Administrator';
        });
        
        Gate::define('manage-vehicles', function (User $user) {
            return $user->role === 'Administrator';
        });
        
        Gate::define('approve-bookings', function (User $user) {
            return in_array($user->role, ['Administrator', 'Approver']);
        });
    }
}


// app/Providers/AuthServiceProvider.php