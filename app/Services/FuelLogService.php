<?php

namespace App\Services;

use App\Models\FuelLog;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class FuelLogService
{
    /**
     * Create a new fuel log entry
     * 
     * Handles the creation of fuel log entries with appropriate validation
     * and business rule enforcement.
     * 
     * @param array $data Fuel log data
     * @return FuelLog The created fuel log
     * @throws \InvalidArgumentException If validation fails
     */
    public function createFuelLog(array $data): FuelLog
    {
        // Validate fuel amount is reasonable
        if (isset($data['liters']) && ($data['liters'] <= 0 || $data['liters'] > 500)) {
            throw new \InvalidArgumentException("Fuel amount must be between 0 and 500 liters");
        }
        
        // Add current user as creator if not specified
        if (!isset($data['created_by'])) {
            $data['created_by'] = Auth::id();
        }
        
        // Calculate consumption metrics if we have odometer readings
        if (isset($data['odometer'])) {
            $this->calculateConsumptionMetrics($data);
        }
        
        // Create and return the fuel log
        return FuelLog::create($data);
    }
    
    /**
     * Calculate consumption metrics based on previous logs
     * 
     * Adds consumption metrics to fuel log data by comparing with previous logs.
     * This enables tracking of fuel efficiency over time.
     * 
     * @param array &$data Fuel log data to be enhanced with metrics
     */
    private function calculateConsumptionMetrics(array &$data): void
    {
        // Find the previous fuel log for this vehicle
        $previousLog = FuelLog::where('vehicle_id', $data['vehicle_id'])
            ->where('odometer', '<', $data['odometer'])
            ->orderBy('odometer', 'desc')
            ->first();
            
        if ($previousLog) {
            // Calculate distance traveled since last fill
            $distanceTraveled = $data['odometer'] - $previousLog->odometer;
            
            // Calculate consumption metrics if we have valid values
            if ($distanceTraveled > 0 && isset($data['liters']) && $data['liters'] > 0) {
                // Calculate consumption in liters per 100km
                $data['consumption_rate'] = ($data['liters'] / $distanceTraveled) * 100;
                
                // Calculate cost per kilometer
                if (isset($data['cost']) && $data['cost'] > 0) {
                    $data['cost_per_km'] = $data['cost'] / $distanceTraveled;
                }
            }
        }
    }
    
    /**
     * Analyze fuel consumption for a vehicle
     * 
     * Provides detailed analysis of fuel consumption patterns for a vehicle,
     * including trends, averages, and cost metrics.
     * 
     * @param Vehicle $vehicle The vehicle to analyze
     * @param Carbon|null $startDate Optional start date for analysis period
     * @param Carbon|null $endDate Optional end date for analysis period
     * @return array Consumption analysis metrics
     */
    public function analyzeFuelConsumption(Vehicle $vehicle, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        // Set default date range if not provided
        $startDate = $startDate ?? Carbon::now()->subMonths(6);
        $endDate = $endDate ?? Carbon::now();
        
        // Get fuel logs for the vehicle in the date range
        $fuelLogs = FuelLog::where('vehicle_id', $vehicle->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'asc')
            ->get();
            
        if ($fuelLogs->isEmpty()) {
            return [
                'total_liters' => 0,
                'total_cost' => 0,
                'avg_price_per_liter' => 0,
                'consumption_data' => []
            ];
        }
        
        // Calculate basic metrics
        $totalLiters = $fuelLogs->sum('liters');
        $totalCost = $fuelLogs->sum('cost');
        $avgPricePerLiter = $totalLiters > 0 ? $totalCost / $totalLiters : 0;
        
        // Calculate consumption data points
        $consumptionData = [];
        for ($i = 1; $i < $fuelLogs->count(); $i++) {
            $current = $fuelLogs[$i];
            $previous = $fuelLogs[$i-1];
            
            // Only include if we have valid odometer readings
            if ($current->odometer > $previous->odometer) {
                $distance = $current->odometer - $previous->odometer;
                $consumptionRate = ($current->liters / $distance) * 100; // L/100km
                
                $consumptionData[] = [
                    'date' => $current->date,
                    'distance' => $distance,
                    'liters' => $current->liters,
                    'consumption_rate' => round($consumptionRate, 2),
                    'cost' => $current->cost,
                    'cost_per_km' => round($current->cost / $distance, 2)
                ];
            }
        }
        
        // Group consumption by month for trend analysis
        $monthlyData = $fuelLogs->groupBy(function($item) {
            return Carbon::parse($item->date)->format('Y-m');
        })->map(function($logs) {
            return [
                'liters' => $logs->sum('liters'),
                'cost' => $logs->sum('cost'),
                'avg_price' => $logs->sum('liters') > 0 ? $logs->sum('cost') / $logs->sum('liters') : 0
            ];
        });
        
        // Return comprehensive analysis
        return [
            'total_liters' => $totalLiters,
            'total_cost' => $totalCost,
            'avg_price_per_liter' => round($avgPricePerLiter, 2),
            'consumption_data' => $consumptionData,
            'monthly_data' => $monthlyData,
            'avg_consumption_rate' => count($consumptionData) > 0 ? 
                round(collect($consumptionData)->avg('consumption_rate'), 2) : null,
            'date_range' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString()
            ]
        ];
    }
    
    /**
     * Compare fuel consumption across the fleet
     * 
     * Analyzes and compares fuel consumption metrics across multiple vehicles,
     * enabling identification of efficiency outliers and trends.
     * 
     * @param array $filters Filter criteria (date range, vehicle types, etc.)
     * @return array Comparative consumption analysis
     */
    public function compareFleetConsumption(array $filters = []): array
    {
        // Set default date range if not provided
        $startDate = $filters['start_date'] ?? Carbon::now()->subMonths(3);
        $endDate = $filters['end_date'] ?? Carbon::now();
        
        // Build base query with filters
        $query = FuelLog::whereBetween('date', [$startDate, $endDate])
            ->when(isset($filters['location_id']), function($q) use ($filters) {
                $q->whereHas('vehicle', function($vq) use ($filters) {
                    $vq->where('location_id', $filters['location_id']);
                });
            })
            ->when(isset($filters['vehicle_type_id']), function($q) use ($filters) {
                $q->whereHas('vehicle', function($vq) use ($filters) {
                    $vq->where('vehicle_type_id', $filters['vehicle_type_id']);
                });
            });
            
        // Get consumption data grouped by vehicle
        $vehicleConsumption = $query->get()
            ->groupBy('vehicle_id')
            ->map(function($logs) {
                // Sort logs by odometer reading
                $sortedLogs = $logs->sortBy('odometer');
                
                // Calculate total metrics
                $totalLiters = $sortedLogs->sum('liters');
                $totalCost = $sortedLogs->sum('cost');
                
                // Calculate consumption rate if we have valid odometer readings
                $consumptionRate = null;
                $firstLog = $sortedLogs->first();
                $lastLog = $sortedLogs->last();
                
                if ($firstLog && $lastLog && $lastLog->odometer > $firstLog->odometer) {
                    $totalDistance = $lastLog->odometer - $firstLog->odometer;
                    $consumptionRate = ($totalLiters / $totalDistance) * 100; // L/100km
                }
                
                // Return comprehensive metrics
                return [
                    'vehicle_id' => $firstLog->vehicle_id,
                    'vehicle' => $firstLog->vehicle->registration_no,
                    'vehicle_type' => $firstLog->vehicle->vehicleType->name,
                    'logs_count' => $sortedLogs->count(),
                    'total_liters' => $totalLiters,
                    'total_cost' => $totalCost,
                    'avg_price_per_liter' => $totalLiters > 0 ? $totalCost / $totalLiters : 0,
                    'consumption_rate' => $consumptionRate ? round($consumptionRate, 2) : null,
                    'cost_per_km' => ($consumptionRate && $totalDistance > 0) ? 
                        round($totalCost / $totalDistance, 2) : null
                ];
            })
            ->values()
            ->toArray();
            
        // Calculate fleet-wide averages
        $fleetAvgConsumption = collect($vehicleConsumption)
            ->whereNotNull('consumption_rate')
            ->avg('consumption_rate');
            
        $fleetAvgCostPerKm = collect($vehicleConsumption)
            ->whereNotNull('cost_per_km')
            ->avg('cost_per_km');
            
        // Return comparative analysis
        return [
            'vehicle_consumption' => $vehicleConsumption,
            'fleet_avg_consumption' => round($fleetAvgConsumption, 2),
            'fleet_avg_cost_per_km' => round($fleetAvgCostPerKm, 2),
            'date_range' => [
                'start' => Carbon::parse($startDate)->toDateString(),
                'end' => Carbon::parse($endDate)->toDateString()
            ],
            'filters' => $filters
        ];
    }
}

//app/Services/FuelLogService.php  

