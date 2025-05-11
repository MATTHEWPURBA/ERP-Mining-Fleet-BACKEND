<?php

namespace App\Listeners;

use App\Events\BookingCreated;
use App\Notifications\NewApprovalRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendNewApprovalRequestNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     *
     * @param BookingCreated $event
     * @return void
     */
    public function handle(BookingCreated $event): void
    {
        $booking = $event->booking;
        
        // Get all approvals for this booking
        $approvals = $booking->approvals;
        
        // Send notification to each approver
        foreach ($approvals as $approval) {
            $approver = $approval->approver;
            $approver->notify(new NewApprovalRequest($approval));
        }
    }
}


// // app/Listeners/SendNewApprovalRequestNotification.php