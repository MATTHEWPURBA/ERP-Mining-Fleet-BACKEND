<?php

namespace App\Providers;

use App\Events\BookingApproved;
use App\Events\BookingCreated;
use App\Events\BookingRejected;
use App\Listeners\SendBookingApprovedNotification;
use App\Listeners\SendBookingRejectedNotification;
use App\Listeners\SendNewApprovalRequestNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        BookingCreated::class => [
            SendNewApprovalRequestNotification::class,
        ],
        BookingApproved::class => [
            SendBookingApprovedNotification::class,
        ],
        BookingRejected::class => [
            SendBookingRejectedNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}



// app/Providers/EventServiceProvider.php