<?php

namespace App\Policies;

use App\Models\BookingApproval;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BookingApprovalPolicy
{
    /**
     * Determine whether the user can view any models.
     * 
     * This permission controls access to the index/list view of booking approvals.
     * We allow all authenticated users to view approvals list, with filtering 
     * applied at the controller level to restrict the results based on role.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view the approvals list
        // Controller will filter results based on user role
        return true;
    }

    /**
     * Determine whether the user can view the model.
     * 
     * This permission controls access to view a specific booking approval.
     * Access is granted if:
     * 1. The user is the designated approver for this booking approval
     * 2. The user is the creator of the associated booking
     * 3. The user is an administrator (has global access)
     */
    public function view(User $user, BookingApproval $bookingApproval): bool
    {
        // Check if user is the designated approver
        if ($user->id === $bookingApproval->approver_id) {
            return true;
        }
        
        // Check if user is the booking creator
        if ($user->id === $bookingApproval->booking->user_id) {
            return true;
        }
        
        // Check if user is an administrator
        if ($user->role === 'Administrator') {
            return true;
        }
        
        // Deny access by default
        return false;
    }

    /**
     * Determine whether the user can create models.
     * 
     * This permission controls direct creation of booking approval records.
     * In normal workflow, approval records are created automatically when a booking
     * is submitted, but administrators might need to manually create approvals.
     */
    public function create(User $user): bool
    {
        // Only administrators can create approval records directly
        // Normal approval records are created automatically during booking submission
        return $user->role === 'Administrator';
    }

    /**
     * Determine whether the user can update the model.
     * 
     * This permission controls updating of booking approval attributes,
     * but not the actual approve/reject actions which are handled separately.
     */
    public function update(User $user, BookingApproval $bookingApproval): bool
    {
        // Only the assigned approver or administrators can update approval records
        return $user->id === $bookingApproval->approver_id || 
               $user->role === 'Administrator';
    }

    /**
     * Determine whether the user can delete the model.
     * 
     * This permission controls deletion of booking approval records.
     * Typically, approval records should not be deleted but managed through
     * status changes. Only administrators have deletion permissions.
     */
    public function delete(User $user, BookingApproval $bookingApproval): bool
    {
        // Only administrators can delete approval records
        // Approvals typically should not be deleted but managed through status changes
        return $user->role === 'Administrator';
    }

    /**
     * Determine whether the user can approve a booking.
     * 
     * This permission controls the ability to approve a booking request.
     * Only the designated approver or administrators can approve a booking.
     */
    public function approve(User $user, BookingApproval $bookingApproval): bool
    {
        // Verify the approval is still pending (can't approve already approved/rejected)
        if ($bookingApproval->status !== 'Pending') {
            return false;
        }
        
        // Only the assigned approver or administrators can approve
        return $user->id === $bookingApproval->approver_id || 
               $user->role === 'Administrator';
    }

    /**
     * Determine whether the user can reject a booking.
     * 
     * This permission controls the ability to reject a booking request.
     * Only the designated approver or administrators can reject a booking.
     */
    public function reject(User $user, BookingApproval $bookingApproval): bool
    {
        // Verify the approval is still pending (can't reject already approved/rejected)
        if ($bookingApproval->status !== 'Pending') {
            return false;
        }
        
        // Only the assigned approver or administrators can reject
        return $user->id === $bookingApproval->approver_id || 
               $user->role === 'Administrator';
    }
}