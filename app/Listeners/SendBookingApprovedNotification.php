<?php

namespace App\Listeners;

use App\Events\BookingApproved;
use App\Notifications\BookingApproved as BookingApprovedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendBookingApprovedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     *
     * @param BookingApproved $event
     * @return void
     */
    public function handle(BookingApproved $event): void
    {
        $booking = $event->booking;
        $user = $booking->user;
        
        // Send notification to the booking requester
        $user->notify(new BookingApprovedNotification($booking));
    }
}

