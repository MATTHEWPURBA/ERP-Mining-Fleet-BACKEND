<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BookingPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view bookings list
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Booking $booking): bool
    {
        // Users can view their own bookings or if they are approvers/admins
        return $user->id === $booking->user_id || 
            in_array($user->role, ['Administrator', 'Approver']) ||
            $booking->approvals()->where('approver_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // All authenticated users can create bookings
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Booking $booking): bool
    {
        // Only the creator can update if status is pending, or admins can update any
        return ($user->id === $booking->user_id && $booking->status === 'Pending') || 
            $user->role === 'Administrator';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Booking $booking): bool
    {
        // Only admins can delete bookings
        return $user->role === 'Administrator';
    }

    /**
     * Determine whether the user can complete the booking.
     */
    public function complete(User $user, Booking $booking): bool
    {
        // Only the creator or admins can mark as completed
        return $user->id === $booking->user_id || $user->role === 'Administrator';
    }

    /**
     * Determine whether the user can cancel the booking.
     */
    public function cancel(User $user, Booking $booking): bool
    {
        // Only the creator can cancel if pending, or admins can cancel any
        return ($user->id === $booking->user_id && in_array($booking->status, ['Pending', 'Approved'])) || 
            $user->role === 'Administrator';
    }
}



// App/Policies/BookingPolicy.php