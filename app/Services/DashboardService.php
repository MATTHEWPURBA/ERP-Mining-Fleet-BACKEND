<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingApproval;
use App\Models\FuelLog;
use App\Models\Maintenance;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Carbon\Carbon;

class DashboardService
{
    /**
     * Get vehicle count statistics by status category
     * 
     * @return array Vehicle counts by different status categories
     */
    public function getVehicleCount(): array
    {
        return [
            'total' => Vehicle::count(),
            'available' => Vehicle::where('status', 'Available')->count(),
            'booked' => Vehicle::where('status', 'Booked')->count(),
            'maintenance' => Vehicle::where('status', 'Maintenance')->count(),
            'rented' => Vehicle::where('is_rented', true)->count()
        ];
    }
    
    /**
     * Get booking statistics by status category
     * 
     * @return array Booking counts by different status categories
     */
    public function getBookingCount(): array
    {
        return [
            'total' => Booking::count(),
            'pending' => Booking::where('status', 'Pending')->count(),
            'approved' => Booking::where('status', 'Approved')->count(),
            'rejected' => Booking::where('status', 'Rejected')->count(),
            'completed' => Booking::where('status', 'Completed')->count(),
            'cancelled' => Booking::where('status', 'Cancelled')->count()
        ];
    }
    
    /**
     * Get count of pending approval requests
     * 
     * @return int Count of pending approvals
     */
    public function getPendingApprovalCount(): int
    {
        return BookingApproval::where('status', 'Pending')->count();
    }
    
    /**
     * Get count of upcoming maintenance within the next 30 days
     * 
     * @return int Count of upcoming maintenance events
     */
    public function getUpcomingMaintenanceCount(): int
    {
        return Maintenance::whereNotNull('next_date')
            ->where('next_date', '>=', now())
            ->where('next_date', '<=', now()->addDays(30))
            ->count();
    }
    
    /**
     * Get vehicle distribution by status for visualization
     * 
     * @return array Status distribution data for charts
     */
    public function getVehicleStatusDistribution(): array
    {
        $statuses = ['Available', 'Booked', 'Maintenance'];
        $result = [];
        
        foreach ($statuses as $status) {
            $result[] = [
                'status' => $status,
                'count' => Vehicle::where('status', $status)->count()
            ];
        }
        
        return $result;
    }
    
    /**
     * Get vehicle distribution by type for visualization
     * 
     * @return array Type distribution data for charts
     */
    public function getVehicleTypeDistribution(): array
    {
        $types = VehicleType::withCount('vehicles')->get();
        
        return $types->map(function($type) {
            return [
                'type' => $type->name,
                'count' => $type->vehicles_count
            ];
        })->toArray();
    }
    
    /**
     * Get most recent booking records
     * 
     * @param int $limit Number of records to retrieve
     * @return array Recent booking records with related data
     */
    public function getRecentBookings(int $limit = 5): array
    {
        return Booking::with(['user', 'vehicle.vehicleType'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
    
    /**
     * Get vehicle utilization data by type for the last 30 days
     * 
     * @return array Utilization metrics by vehicle type
     */
    public function getVehicleUtilization(): array
    {
        // Define date range for analysis
        $startDate = now()->subDays(30);
        $endDate = now();
        
        // Get approved bookings within date range
        $bookings = Booking::whereBetween('start_date', [$startDate, $endDate])
            ->where('status', 'Approved')
            ->get();
            
        // Get all vehicle types for categorization
        $vehicleTypes = VehicleType::all();
        $result = [];
        
        // Calculate utilization for each vehicle type
        foreach ($vehicleTypes as $type) {
            // Filter bookings for this vehicle type
            $typeBookings = $bookings->filter(function($booking) use ($type) {
                return $booking->vehicle->vehicle_type_id === $type->id;
            });
            
            // Calculate total usage hours
            $totalHours = 0;
            foreach ($typeBookings as $booking) {
                $start = Carbon::parse($booking->start_date);
                $end = Carbon::parse($booking->end_date);
                $totalHours += $end->diffInHours($start);
            }
            
            // Add utilization data to results
            $result[] = [
                'type' => $type->name,
                'hours' => $totalHours,
                'bookings' => $typeBookings->count()
            ];
        }
        
        return $result;
    }
    
    /**
     * Get fuel consumption data for the last 12 months
     * 
     * @return array Monthly fuel consumption metrics
     */
    public function getFuelConsumptionData(): array
    {
        // Define date range for analysis
        $startDate = now()->subMonths(12)->startOfMonth();
        $endDate = now()->endOfMonth();
        
        // Get fuel logs within date range
        $fuelLogs = FuelLog::whereBetween('date', [$startDate, $endDate])
            ->get();
            
        // Group logs by month for trend analysis
        $monthlyData = $fuelLogs->groupBy(function($item) {
            return Carbon::parse($item->date)->format('Y-m');
        });
        
        // Calculate monthly summaries
        $result = [];
        foreach ($monthlyData as $month => $logs) {
            $result[] = [
                'month' => $month,
                'liters' => $logs->sum('liters'),
                'cost' => $logs->sum('cost')
            ];
        }
        
        return $result;
    }
    
    /**
     * Get bookings for a specific user
     * 
     * @param int $userId User ID to retrieve bookings for
     * @return array User's booking records with related data
     */
    public function getUserBookings(int $userId): array
    {
        return Booking::where('user_id', $userId)
            ->with(['vehicle.vehicleType', 'approvals.approver'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->toArray();
    }
    
    /**
     * Get pending approvals for a specific approver
     * 
     * @param int $userId Approver's user ID
     * @return array Pending approval records with related booking data
     */
    public function getUserPendingApprovals(int $userId): array
    {
        return BookingApproval::where('approver_id', $userId)
            ->where('status', 'Pending')
            ->with(['booking.user', 'booking.vehicle.vehicleType'])
            ->get()
            ->toArray();
    }
    
    /**
     * Get future approved bookings for a specific user
     * 
     * @param int $userId User ID
     * @return array Upcoming booking records
     */
    public function getUserUpcomingBookings(int $userId): array
    {
        return Booking::where('user_id', $userId)
            ->where('status', 'Approved')
            ->where('start_date', '>=', now())
            ->with(['vehicle.vehicleType'])
            ->orderBy('start_date')
            ->limit(5)
            ->get()
            ->toArray();
    }
    
    /**
     * Get recently used vehicles for a specific user
     * 
     * @param int $userId User ID
     * @return array Recently used vehicle records
     */
    public function getUserRecentlyUsedVehicles(int $userId): array
    {
        // Get unique vehicle IDs from completed bookings
        $vehicleIds = Booking::where('user_id', $userId)
            ->where('status', 'Completed')
            ->orderBy('end_date', 'desc')
            ->limit(10)
            ->pluck('vehicle_id')
            ->unique()
            ->toArray();
            
        // Retrieve vehicle details with related data
        return Vehicle::whereIn('id', $vehicleIds)
            ->with('vehicleType', 'location')
            ->get()
            ->toArray();
    }
}
