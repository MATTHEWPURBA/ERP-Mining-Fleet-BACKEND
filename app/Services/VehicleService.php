<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\Booking;
use Carbon\Carbon;

class VehicleService
{
    /**
     * Find available vehicles based on criteria
     * 
     * This method implements a sophisticated vehicle search algorithm that:
     * 1. Excludes vehicles already booked during the requested timeframe
     * 2. Filters by location, type, and other criteria as needed
     * 3. Handles edge cases like overlapping bookings
     * 
     * @param array $criteria Search criteria including dates, location, type
     * @return \Illuminate\Database\Eloquent\Collection Collection of available vehicles
     */
    public function findAvailableVehicles(array $criteria): \Illuminate\Database\Eloquent\Collection
    {
        // Extract and validate the search criteria
        $startDate = Carbon::parse($criteria['start_date']);
        $endDate = Carbon::parse($criteria['end_date']);
        
        // Find vehicles that are already booked during this period using complex date range logic
        // This handles all booking overlap scenarios: contained, containing, overlapping start, overlapping end
        $bookedVehicleIds = Booking::where(function($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<', $startDate)
                          ->where('end_date', '>', $endDate);
                    });
            })
            ->whereIn('status', ['Pending', 'Approved']) // Only consider active bookings
            ->pluck('vehicle_id')
            ->toArray();
        
        // Query for available vehicles based on the criteria and exclusions
        return Vehicle::with(['vehicleType', 'location'])
            ->where('status', 'Available') // Only vehicles marked as available
            ->whereNotIn('id', $bookedVehicleIds) // Exclude already booked vehicles
            ->when(isset($criteria['location_id']), function($query) use ($criteria) {
                $query->where('location_id', $criteria['location_id']);
            })
            ->when(isset($criteria['vehicle_type_id']), function($query) use ($criteria) {
                $query->where('vehicle_type_id', $criteria['vehicle_type_id']);
            })
            ->when(isset($criteria['capacity']), function($query) use ($criteria) {
                $query->whereHas('vehicleType', function($q) use ($criteria) {
                    $q->where('capacity', '>=', $criteria['capacity']);
                });
            })
            ->get();
    }
    
    /**
     * Update vehicle status
     * 
     * Centralized method to update vehicle status with validation and business rules.
     * This provides a single point of control for status transitions and ensures
     * consistent application of business rules.
     * 
     * @param Vehicle $vehicle The vehicle to update
     * @param string $status New status ('Available', 'Booked', 'Maintenance')
     * @param string|null $reason Optional reason for the status change (for logging)
     * @return Vehicle Updated vehicle instance
     * @throws \InvalidArgumentException If status is invalid
     */
    public function updateVehicleStatus(Vehicle $vehicle, string $status, ?string $reason = null): Vehicle
    {
        // Validate the status is allowed
        $allowedStatuses = ['Available', 'Booked', 'Maintenance'];
        if (!in_array($status, $allowedStatuses)) {
            throw new \InvalidArgumentException("Invalid vehicle status: {$status}");
        }
        
        // Handle business rules for status transitions
        if ($vehicle->status === 'Booked' && $status === 'Available') {
            // Verify no active bookings exist for this vehicle
            $activeBookingsExist = Booking::where('vehicle_id', $vehicle->id)
                ->whereIn('status', ['Pending', 'Approved'])
                ->where('end_date', '>', now())
                ->exists();
                
            if ($activeBookingsExist) {
                throw new \InvalidArgumentException("Cannot mark vehicle as Available while active bookings exist");
            }
        }
        
        // Update the vehicle status
        $vehicle->status = $status;
        $vehicle->save();
        
        // Optional: Log the status change if needed
        // activity()->performedOn($vehicle)->log("Vehicle status changed to {$status}. Reason: {$reason}");
        
        return $vehicle;
    }
    
    /**
     * Transfer vehicle between locations
     * 
     * Handles the business logic for transferring vehicles between locations,
     * including validation, status updates, and related operations.
     * 
     * @param Vehicle $vehicle Vehicle to transfer
     * @param int $newLocationId ID of the destination location
     * @param array $options Additional options for the transfer
     * @return Vehicle Updated vehicle instance
     * @throws \InvalidArgumentException If transfer is not allowed
     */
    public function transferVehicle(Vehicle $vehicle, int $newLocationId, array $options = []): Vehicle
    {
        // Verify the vehicle is available for transfer
        if ($vehicle->status !== 'Available') {
            throw new \InvalidArgumentException("Only available vehicles can be transferred");
        }
        
        // Check for pending bookings
        $pendingBookings = Booking::where('vehicle_id', $vehicle->id)
            ->whereIn('status', ['Pending', 'Approved'])
            ->where('start_date', '<=', now()->addDays(isset($options['grace_period']) ? $options['grace_period'] : 7))
            ->exists();
            
        if ($pendingBookings) {
            throw new \InvalidArgumentException("Vehicle has upcoming bookings and cannot be transferred");
        }
        
        // Update the vehicle's location
        $oldLocationId = $vehicle->location_id;
        $vehicle->location_id = $newLocationId;
        $vehicle->save();
        
        // Optional: Log the transfer
        // activity()->performedOn($vehicle)
        //     ->withProperties(['from_location' => $oldLocationId, 'to_location' => $newLocationId])
        //     ->log("Vehicle transferred to new location");
        
        return $vehicle;
    }
    
    /**
     * Get vehicle usage statistics
     * 
     * Calculates detailed usage metrics for a vehicle over a specified time period,
     * including utilization rate, total bookings, and time distribution.
     * 
     * @param Vehicle $vehicle The vehicle to analyze
     * @param Carbon $startDate Start of analysis period
     * @param Carbon $endDate End of analysis period
     * @return array Usage statistics
     */
    public function getVehicleUsageStats(Vehicle $vehicle, Carbon $startDate, Carbon $endDate): array
    {
        // Get all bookings for this vehicle in the date range
        $bookings = Booking::where('vehicle_id', $vehicle->id)
            ->whereIn('status', ['Approved', 'Completed'])
            ->where(function($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<', $startDate)
                          ->where('end_date', '>', $endDate);
                    });
            })
            ->get();
        
        // Calculate total booked hours
        $bookedHours = 0;
        foreach ($bookings as $booking) {
            // Ensure we only count the portion within our date range
            $bookingStart = max($startDate, Carbon::parse($booking->start_date));
            $bookingEnd = min($endDate, Carbon::parse($booking->end_date));
            $bookedHours += $bookingEnd->diffInHours($bookingStart);
        }
        
        // Calculate total hours in the period and utilization rate
        $totalHours = $endDate->diffInHours($startDate);
        $utilizationRate = ($totalHours > 0) ? round(($bookedHours / $totalHours) * 100, 2) : 0;
        
        // Return comprehensive statistics
        return [
            'total_bookings' => $bookings->count(),
            'booked_hours' => $bookedHours,
            'total_hours' => $totalHours,
            'utilization_rate' => $utilizationRate,
            'users' => $bookings->pluck('user_id')->unique()->count(), // Unique users who booked this vehicle
            'average_booking_duration' => $bookings->count() > 0 ? round($bookedHours / $bookings->count(), 2) : 0,
            'most_recent_booking' => $bookings->sortByDesc('end_date')->first()
        ];
    }
}

// app/Services/VehicleService.php