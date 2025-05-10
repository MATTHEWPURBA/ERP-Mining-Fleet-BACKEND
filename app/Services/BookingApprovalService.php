<?php

namespace App\Services;

use App\Events\BookingApproved;
use App\Events\BookingRejected;
use App\Models\BookingApproval;
use App\Models\Vehicle;

class BookingApprovalService
{
    /**
     * Approve a booking approval request
     * 
     * @param BookingApproval $approval The approval record to update
     * @param string|null $comments Optional comments for the approval
     * @return BookingApproval The updated approval with related data
     */
    public function approveBooking(BookingApproval $approval, ?string $comments = null): BookingApproval
    {
        // Update the approval status and add any comments
        $approval->status = 'Approved';
        $approval->comments = $comments;
        $approval->save();
        
        // Get the associated booking record
        $booking = $approval->booking;
        
        // Check if all approvals are completed for this booking
        $pendingApprovals = BookingApproval::where('booking_id', $booking->id)
            ->where('status', 'Pending')
            ->count();
            
        // If no pending approvals remain, the booking is fully approved
        if ($pendingApprovals === 0) {
            // Update booking status to Approved
            $booking->status = 'Approved';
            $booking->save();
            
            // Fire event to trigger notification system
            event(new BookingApproved($booking));
        }
        
        // Return approval with related data for comprehensive response
        return $approval->load('booking.user', 'booking.vehicle.vehicleType');
    }
    
    /**
     * Reject a booking approval request
     * 
     * @param BookingApproval $approval The approval record to reject
     * @param string|null $comments Optional rejection reason
     * @return BookingApproval The updated approval with related data
     */
    public function rejectBooking(BookingApproval $approval, ?string $comments = null): BookingApproval
    {
        // Update the approval status and add rejection comments
        $approval->status = 'Rejected';
        $approval->comments = $comments;
        $approval->save();
        
        // Get the associated booking
        $booking = $approval->booking;
        
        // Update booking status to Rejected
        $booking->status = 'Rejected';
        $booking->save();
        
        // Release the vehicle by updating its status back to Available
        $vehicle = Vehicle::find($booking->vehicle_id);
        $vehicle->status = 'Available';
        $vehicle->save();
        
        // Fire event to trigger rejection notification
        event(new BookingRejected($booking));
        
        // Return approval with related data for comprehensive response
        return $approval->load('booking.user', 'booking.vehicle.vehicleType');
    }
}
