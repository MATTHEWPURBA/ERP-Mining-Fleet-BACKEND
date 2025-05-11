<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\MaintenanceRequest;
use App\Models\Maintenance;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    public function index(Request $request)
    {
        $maintenance = Maintenance::with('vehicle.vehicleType')
            ->when($request->search, function($query, $search) {
                $query->where('description', 'like', "%{$search}%")
                      ->orWhere('type', 'like', "%{$search}%");
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
            ->when($request->type, function($query, $type) {
                $query->where('type', $type);
            })
            ->orderBy('date', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($maintenance);
    }

    public function store(MaintenanceRequest $request)
    {
        $validated = $request->validated();
        $maintenance = Maintenance::create($validated);
        
        // Update vehicle status if it's going into maintenance
        if ($maintenance) {
            $vehicle = Vehicle::find($maintenance->vehicle_id);
            $vehicle->status = 'Maintenance';
            $vehicle->save();
        }
        
        return response()->json([
            'message' => 'Maintenance record created successfully',
            'maintenance' => $maintenance->load('vehicle.vehicleType')
        ], 201);
    }

    public function show(Maintenance $maintenance)
    {
        return response()->json($maintenance->load('vehicle.vehicleType'));
    }

    public function update(MaintenanceRequest $request, Maintenance $maintenance)
    {
        $validated = $request->validated();
        $maintenance->update($validated);
        
        return response()->json([
            'message' => 'Maintenance record updated successfully',
            'maintenance' => $maintenance->fresh(['vehicle.vehicleType'])
        ]);
    }

    public function destroy(Maintenance $maintenance)
    {
        try {
            $maintenance->delete();
            
            // Check if this was the last maintenance record, update vehicle status if needed
            $hasOtherMaintenance = Maintenance::where('vehicle_id', $maintenance->vehicle_id)
                ->where('id', '!=', $maintenance->id)
                ->exists();
                
            if (!$hasOtherMaintenance) {
                $vehicle = Vehicle::find($maintenance->vehicle_id);
                $vehicle->status = 'Available';
                $vehicle->save();
            }
            
            return response()->json(['message' => 'Maintenance record deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Cannot delete maintenance record.'], 400);
        }
    }

    public function upcoming()
    {
        $upcoming = Maintenance::with('vehicle.vehicleType')
            ->whereNotNull('next_date')
            ->where('next_date', '>=', now())
            ->where('next_date', '<=', now()->addDays(30))
            ->orderBy('next_date')
            ->get();
            
        return response()->json($upcoming);
    }
}


// app/Http/Controllers/API/MaintenanceController.php