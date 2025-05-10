<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\FuelLogRequest;
use App\Models\FuelLog;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FuelLogController extends Controller
{
    public function index(Request $request)
    {
        $fuelLogs = FuelLog::with(['vehicle.vehicleType', 'creator'])
            ->when($request->search, function($query, $search) {
                $query->whereHas('vehicle', function($q) use ($search) {
                    $q->where('registration_no', 'like', "%{$search}%");
                });
            })
            ->when($request->vehicle_id, function($query, $vehicleId) {
                $query->where('vehicle_id', $vehicleId);
            })
            ->when($request->start_date, function($query, $startDate) {
                $query->where('date', '>=', $startDate);
            })
            ->when($request->end_date, function($query, $endDate) {
                $query->where('date', '<=', $endDate);
            })
            ->orderBy('date', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($fuelLogs);
    }

    public function store(FuelLogRequest $request)
    {
        $validated = $request->validated();
        $validated['created_by'] = $request->user()->id;
        
        $fuelLog = FuelLog::create($validated);
        
        return response()->json([
            'message' => 'Fuel log created successfully',
            'fuel_log' => $fuelLog->load(['vehicle.vehicleType', 'creator'])
        ], 201);
    }

    public function show(FuelLog $fuelLog)
    {
        return response()->json($fuelLog->load(['vehicle.vehicleType', 'creator']));
    }

    public function update(FuelLogRequest $request, FuelLog $fuelLog)
    {
        $validated = $request->validated();
        $fuelLog->update($validated);
        
        return response()->json([
            'message' => 'Fuel log updated successfully',
            'fuel_log' => $fuelLog->fresh(['vehicle.vehicleType', 'creator'])
        ]);
    }

    public function destroy(FuelLog $fuelLog)
    {
        try {
            $fuelLog->delete();
            return response()->json(['message' => 'Fuel log deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Cannot delete fuel log.'], 400);
        }
    }

    public function vehicleConsumption(Vehicle $vehicle)
    {
        $fuelLogs = FuelLog::where('vehicle_id', $vehicle->id)
            ->orderBy('date')
            ->get();
            
        $totalLiters = $fuelLogs->sum('liters');
        $totalCost = $fuelLogs->sum('cost');
        $consumption = [];
        
        // Calculate consumption by month
        $monthlyData = $fuelLogs->groupBy(function($item) {
            return Carbon::parse($item->date)->format('Y-m');
        });
        
        foreach ($monthlyData as $month => $logs) {
            $consumption[] = [
                'month' => $month,
                'liters' => $logs->sum('liters'),
                'cost' => $logs->sum('cost')
            ];
        }
        
        return response()->json([
            'total_liters' => $totalLiters,
            'total_cost' => $totalCost,
            'monthly_consumption' => $consumption
        ]);
    }
}
