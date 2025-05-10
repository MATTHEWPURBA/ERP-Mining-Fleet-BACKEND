<?php

namespace App\Services;

use App\Events\BookingCreated;
use App\Models\Booking;
use App\Models\BookingApproval;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Contracts\Auth\Factory as AuthFactory;


class BookingService
{
    /**
     * The authentication factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;
    
    /**
     * Create a new booking service instance.
     *
     * @param \Illuminate\Contracts\Auth\Factory $auth
     * @return void
     */
    public function __construct(AuthFactory $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Create a new booking and set up approval workflow
     * 
     * @param array $data Booking data including user_id, vehicle_id, purpose, start_date, end_date, etc.
     * @return Booking The newly created booking with related data loaded
     */
    public function createBooking(array $data): Booking
    {
        // Create booking record with provided data
        $booking = Booking::create($data);
        
        // Create approval workflow based on organizational hierarchy
        $this->createApprovalWorkflow($booking);
        
        // Update vehicle status to reflect booking
        $vehicle = Vehicle::find($booking->vehicle_id);
        $vehicle->status = 'Booked';
        $vehicle->save();
        
        // Fire event to trigger notifications to relevant stakeholders
        event(new BookingCreated($booking));
        
        // Return booking with eager-loaded relationships for comprehensive data access
        return $booking->load(['user', 'vehicle.vehicleType', 'approvals.approver']);
    }

    /**
     * Update an existing booking with new data
     * 
     * @param Booking $booking The booking to update
     * @param array $data New booking data
     * @return Booking The updated booking
     * @throws \Exception If booking cannot be updated due to status constraints
     */
    public function updateBooking(Booking $booking, array $data): Booking
    {
        // Get current user through the guard() method which is defined in the AuthFactory interface
        // This approach is more explicit and will resolve the PHPIntelephense error
        $currentUser = $this->auth->guard()->user();
        
        // Verify update permissions: only pending bookings can be updated (unless admin)
        if ($booking->status !== 'Pending' && $currentUser && $currentUser->role !== 'Administrator') {
            throw new \Exception('Cannot update booking that is not in pending status.');
        }
        
        // Update booking with new data
        $booking->update($data);
        
        // Special handling for vehicle changes which require rebuilding the approval workflow
        if (isset($data['vehicle_id']) && $data['vehicle_id'] != $booking->getOriginal('vehicle_id')) {
            // Remove existing approval records to create fresh workflow
            BookingApproval::where('booking_id', $booking->id)->delete();
            
            // Create new approval workflow for updated booking
            $this->createApprovalWorkflow($booking);
            
            // Update vehicle statuses to reflect changes: original vehicle freed, new vehicle booked
            Vehicle::where('id', $booking->getOriginal('vehicle_id'))->update(['status' => 'Available']);
            Vehicle::where('id', $booking->vehicle_id)->update(['status' => 'Booked']);
        }
        
        return $booking;
    }




    /**
     * Create the approval workflow for a booking based on organizational hierarchy
     * 
     * @param Booking $booking The booking requiring approvals
     */
    private function createApprovalWorkflow(Booking $booking): void
    {
        // Get the booking requester's user record
        $user = User::find($booking->user_id);
        
        // If user has a supervisor, use supervisor chain for approvals
        if ($user->supervisor_id) {
            // First level approval - direct supervisor
            BookingApproval::create([
                'booking_id' => $booking->id,
                'approver_id' => $user->supervisor_id,
                'level' => 1,
                'status' => 'Pending'
            ]);
            
            // Check for second level approval - supervisor's supervisor or admin
            $supervisor = User::find($user->supervisor_id);
            if ($supervisor->supervisor_id) {
                // Second level approval from higher management
                BookingApproval::create([
                    'booking_id' => $booking->id,
                    'approver_id' => $supervisor->supervisor_id,
                    'level' => 2,
                    'status' => 'Pending'
                ]);
            } else {
                // If no higher supervisor, find an Administrator for second level
                $admin = User::where('role', 'Administrator')->first();
                if ($admin && $admin->id !== $user->supervisor_id) {
                    BookingApproval::create([
                        'booking_id' => $booking->id,
                        'approver_id' => $admin->id,
                        'level' => 2,
                        'status' => 'Pending'
                    ]);
                }
            }
        } else {
            // No supervisor, assign to Administrator and Approver roles
            $admin = User::where('role', 'Administrator')->first();
            if ($admin) {
                BookingApproval::create([
                    'booking_id' => $booking->id,
                    'approver_id' => $admin->id,
                    'level' => 1,
                    'status' => 'Pending'
                ]);
            }
            
            // Find a different approver for the second level
            $approver = User::where('role', 'Approver')
                ->where('id', '!=', $admin->id ?? null)
                ->first();
                
            if ($approver) {
                BookingApproval::create([
                    'booking_id' => $booking->id,
                    'approver_id' => $approver->id,
                    'level' => 2,
                    'status' => 'Pending'
                ]);
            }
        }
    }
}
