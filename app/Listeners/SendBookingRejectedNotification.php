<?php

namespace App\Listeners;

use App\Events\BookingRejected;
use App\Notifications\BookingRejected as BookingRejectedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendBookingRejectedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     *
     * @param BookingRejected $event
     * @return void
     */
    public function handle(BookingRejected $event): void
    {
        $booking = $event->booking;
        $user = $booking->user;
        
        // Get the rejection comments from the most recent rejected approval
        $latestRejection = $booking->approvals()->where('status', 'Rejected')->latest()->first();
        $comments = $latestRejection ? $latestRejection->comments : null;
        
        // Send notification to the booking requester
        $user->notify(new BookingRejectedNotification($booking, $comments));
    }
}
