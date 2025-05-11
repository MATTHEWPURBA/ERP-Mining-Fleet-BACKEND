<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookingRequest;
use App\Models\Booking;
use App\Models\Vehicle;
use App\Services\BookingService;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    private $bookingService;
    
    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }
    
    public function index(Request $request)
    {
        $user = $request->user();
        $bookings = Booking::with(['user', 'vehicle.vehicleType', 'approvals.approver'])
            ->when($request->search, function($query, $search) {
                $query->where('purpose', 'like', "%{$search}%")
                      ->orWhere('notes', 'like', "%{$search}%");
            })
            ->when($request->status, function($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->start_date, function($query, $startDate) {
                $query->where('start_date', '>=', $startDate);
            })
            ->when($request->end_date, function($query, $endDate) {
                $query->where('end_date', '<=', $endDate);
            })
            ->when($request->user_id, function($query, $userId) {
                $query->where('user_id', $userId);
            })
            ->when($request->vehicle_id, function($query, $vehicleId) {
                $query->where('vehicle_id', $vehicleId);
            })
            // If not admin, show only user's bookings or those they need to approve
            ->when($user->role !== 'Administrator', function($query) use ($user) {
                $query->where(function($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->orWhereHas('approvals', function($a) use ($user) {
                          $a->where('approver_id', $user->id);
                      });
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($bookings);
    }

    public function store(BookingRequest $request)
    {
        $validated = $request->validated();
        $validated['user_id'] = $request->user()->id;
        $validated['status'] = 'Pending';
        
        $booking = $this->bookingService->createBooking($validated);
        
        return response()->json([
            'message' => 'Booking created successfully',
            'booking' => $booking->load(['user', 'vehicle.vehicleType', 'approvals.approver'])
        ], 201);
    }

    public function show(Booking $booking)
    {
        $this->authorize('view', $booking);
        return response()->json($booking->load(['user', 'vehicle.vehicleType', 'approvals.approver']));
    }

    public function update(BookingRequest $request, Booking $booking)
    {
        $this->authorize('update', $booking);
        
        $validated = $request->validated();
        $booking = $this->bookingService->updateBooking($booking, $validated);
        
        return response()->json([
            'message' => 'Booking updated successfully',
            'booking' => $booking->fresh(['user', 'vehicle.vehicleType', 'approvals.approver'])
        ]);
    }

    public function destroy(Booking $booking)
    {
        $this->authorize('delete', $booking);
        
        try {
            $booking->delete();
            return response()->json(['message' => 'Booking deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Cannot delete booking.'], 400);
        }
    }
    
    public function cancel(Booking $booking)
    {
        $this->authorize('cancel', $booking);
        
        $booking->status = 'Cancelled';
        $booking->save();
        
        return response()->json([
            'message' => 'Booking cancelled successfully',
            'booking' => $booking->fresh(['user', 'vehicle.vehicleType', 'approvals.approver'])
        ]);
    }




/**
 * Mark a booking as completed and update related vehicle status
 *
 * @param \App\Models\Booking $booking
 * @return \Illuminate\Http\JsonResponse
 */
public function complete(Booking $booking)
{
    // Check authorization using the BookingPolicy's complete method
    // This ensures only authorized users (booking creator or administrators) can complete bookings
    $this->authorize('complete', $booking);
    
    // Update booking status to 'Completed'
    // This status change triggers business logic that may be used by reports or dashboard widgets
    $booking->status = 'Completed';
    $booking->save();
    
    // Update vehicle status back to 'Available' since the booking is now complete
    // This is crucial to ensure the vehicle becomes available in the system for new bookings
    $vehicle = Vehicle::find($booking->vehicle_id);
    $vehicle->status = 'Available';
    $vehicle->save();
    
    // Return success response with the updated booking data
    // We use fresh() with eager loading to ensure we get the latest data with all relationships
    return response()->json([
        'message' => 'Booking marked as completed successfully',
        'booking' => $booking->fresh(['user', 'vehicle.vehicleType', 'approvals.approver'])
    ]);
}


}



// app/Http/Controllers/API/BookingController.php