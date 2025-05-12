<?php

namespace App\Services;

use App\Models\Maintenance;
use App\Models\Vehicle;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MaintenanceService
{
    /**
     * Schedule vehicle maintenance
     * 
     * Creates a maintenance record and updates vehicle status accordingly.
     * Handles all the business logic around maintenance scheduling including
     * conflict resolution with existing bookings.
     * 
     * @param array $data Maintenance details
     * @return Maintenance The created maintenance record
     * @throws \Exception If maintenance cannot be scheduled
     */
    public function scheduleMaintenance(array $data): Maintenance
    {
        // Validate maintenance date against existing bookings
        $vehicle = Vehicle::findOrFail($data['vehicle_id']);
        $maintenanceDate = Carbon::parse($data['date']);
        
        // Check for booking conflicts if this is unplanned maintenance
        if (isset($data['type']) && $data['type'] === 'Unscheduled') {
            $conflictingBookings = Booking::where('vehicle_id', $vehicle->id)
                ->whereIn('status', ['Approved'])
                ->where(function($query) use ($maintenanceDate) {
                    // Simple conflict check for same-day maintenance
                    $query->whereDate('start_date', '=', $maintenanceDate->toDateString())
                        ->orWhereDate('end_date', '=', $maintenanceDate->toDateString());
                })
                ->get();
                
            if ($conflictingBookings->count() > 0) {
                // Log the conflict but proceed (unscheduled maintenance takes priority)
                Log::warning("Maintenance scheduled despite booking conflicts", [
                    'vehicle_id' => $vehicle->id,
                    'date' => $maintenanceDate->toDateString(),
                    'conflicting_bookings' => $conflictingBookings->pluck('id')->toArray()
                ]);
                
                // Optional: Notify affected users about the booking cancellation
                // foreach ($conflictingBookings as $booking) {
                //    $this->notifyMaintenanceConflict($booking, $maintenanceDate);
                // }
            }
        }
        
        // Create the maintenance record
        $maintenance = Maintenance::create($data);
        
        // Update vehicle status to Maintenance
        $vehicle->status = 'Maintenance';
        $vehicle->save();
        
        // If this is scheduled maintenance with a next_date, we might want to
        // pre-create the next maintenance record in the calendar
        if (isset($data['type']) && $data['type'] === 'Scheduled' && isset($data['next_date'])) {
            $this->scheduleNextMaintenance($maintenance);
        }
        
        return $maintenance;
    }
    
    /**
     * Schedule next maintenance based on current maintenance
     * 
     * This method pre-schedules the next maintenance based on the current maintenance's
     * next_date field, creating a maintenance calendar for preventive maintenance.
     * 
     * @param Maintenance $currentMaintenance Current maintenance record
     * @return Maintenance|null The pre-scheduled next maintenance record, or null if not applicable
     */
    private function scheduleNextMaintenance(Maintenance $currentMaintenance): ?Maintenance
    {
        // Only schedule next maintenance if a next_date is set
        if (!$currentMaintenance->next_date) {
            return null;
        }
        
        // Create a reminder record for the next maintenance date
        // This doesn't change vehicle status but serves as a calendar entry
        return Maintenance::create([
            'vehicle_id' => $currentMaintenance->vehicle_id,
            'type' => 'Scheduled',
            'description' => 'Scheduled maintenance - ' . $currentMaintenance->description,
            'cost' => 0, // Will be updated when the actual maintenance occurs
            'date' => $currentMaintenance->next_date,
            'next_date' => null // To be determined at the time of maintenance
        ]);
    }
    
    /**
     * Complete a maintenance record
     * 
     * Updates the maintenance record with completion details and
     * returns the vehicle to available status if appropriate.
     * 
     * @param Maintenance $maintenance The maintenance to complete
     * @param array $data Completion details including actual costs, notes
     * @return Maintenance The updated maintenance record
     */
    public function completeMaintenance(Maintenance $maintenance, array $data): Maintenance
    {
        // Update the maintenance record with completion details
        $maintenance->update([
            'cost' => $data['cost'] ?? $maintenance->cost,
            'description' => $data['description'] ?? $maintenance->description,
            'next_date' => $data['next_date'] ?? $maintenance->next_date,
            // Add any other completion fields as needed
        ]);
        
        // Update vehicle status back to Available
        $vehicle = Vehicle::find($maintenance->vehicle_id);
        
        // Check if there are other active maintenance records for this vehicle
        $activeMaintenanceExists = Maintenance::where('vehicle_id', $vehicle->id)
            ->where('id', '!=', $maintenance->id)
            ->where('date', '>=', now()->startOfDay())
            ->where('date', '<=', now()->endOfDay())
            ->exists();
            
        if (!$activeMaintenanceExists) {
            $vehicle->status = 'Available';
            $vehicle->save();
        }
        
        return $maintenance;
    }
    
    /**
     * Find vehicles due for maintenance
     * 
     * Identifies vehicles that are due for scheduled maintenance based on
     * their maintenance records and schedules.
     * 
     * @param int $daysAhead Number of days to look ahead (default 30)
     * @return \Illuminate\Database\Eloquent\Collection Vehicles due for maintenance
     */
    public function findVehiclesDueForMaintenance(int $daysAhead = 30): \Illuminate\Database\Eloquent\Collection
    {
        // Find maintenance records with upcoming next_date
        $upcomingMaintenance = Maintenance::whereNotNull('next_date')
            ->where('next_date', '>=', now())
            ->where('next_date', '<=', now()->addDays($daysAhead))
            ->with('vehicle.vehicleType')
            ->get();
            
        // Extract unique vehicles from these maintenance records
        return $upcomingMaintenance->map(function ($maintenance) {
            return $maintenance->vehicle;
        })->unique('id')->values();
    }
    
    /**
     * Calculate maintenance cost statistics
     * 
     * Analyzes maintenance costs by vehicle, type, time period, etc.
     * Provides detailed cost metrics for financial reporting.
     * 
     * @param array $filters Filter criteria (date range, vehicle type, etc.)
     * @return array Detailed maintenance cost statistics
     */
    public function calculateMaintenanceCostStats(array $filters = []): array
    {
        // Base query with filters
        $query = Maintenance::query()
            ->when(isset($filters['start_date']), function($q) use ($filters) {
                $q->where('date', '>=', $filters['start_date']);
            })
            ->when(isset($filters['end_date']), function($q) use ($filters) {
                $q->where('date', '<=', $filters['end_date']);
            })
            ->when(isset($filters['vehicle_id']), function($q) use ($filters) {
                $q->where('vehicle_id', $filters['vehicle_id']);
            })
            ->when(isset($filters['vehicle_type_id']), function($q) use ($filters) {
                $q->whereHas('vehicle', function($vq) use ($filters) {
                    $vq->where('vehicle_type_id', $filters['vehicle_type_id']);
                });
            });
        
        // Calculate overall stats
        $totalCost = $query->sum('cost');
        $recordCount = $query->count();
        $avgCost = $recordCount > 0 ? $totalCost / $recordCount : 0;
        
        // Calculate costs by maintenance type
        $costByType = $query->clone()
            ->selectRaw('type, SUM(cost) as total_cost, COUNT(*) as count')
            ->groupBy('type')
            ->get()
            ->map(function($item) {
                return [
                    'type' => $item->type,
                    'total_cost' => $item->total_cost,
                    'count' => $item->count,
                    'avg_cost' => $item->count > 0 ? $item->total_cost / $item->count : 0
                ];
            });
            
        // Return comprehensive statistics
        return [
            'total_cost' => $totalCost,
            'record_count' => $recordCount,
            'avg_cost_per_record' => $avgCost,
            'by_type' => $costByType,
            'filters' => $filters
        ];
    }
}

// app/Services/MaintenanceService.php