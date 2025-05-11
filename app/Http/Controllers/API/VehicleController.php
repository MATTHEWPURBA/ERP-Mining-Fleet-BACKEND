<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\VehicleRequest;
use App\Models\Vehicle;
use App\Models\Booking;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index(Request $request)
    {
        $vehicles = Vehicle::with(['vehicleType', 'location'])
            ->when($request->search, function($query, $search) {
                $query->where('registration_no', 'like', "%{$search}%");
            })
            ->when($request->status, function($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->vehicle_type_id, function($query, $vehicleTypeId) {
                $query->where('vehicle_type_id', $vehicleTypeId);
            })
            ->when($request->location_id, function($query, $locationId) {
                $query->where('location_id', $locationId);
            })
            ->when($request->is_rented, function($query, $isRented) {
                $query->where('is_rented', $isRented == 'true' ? 1 : 0);
            })
            ->orderBy('registration_no')
            ->paginate($request->per_page ?? 15);

        return response()->json($vehicles);
    }

    public function store(VehicleRequest $request)
    {
        $validated = $request->validated();
        $vehicle = Vehicle::create($validated);
        
        return response()->json([
            'message' => 'Vehicle created successfully',
            'vehicle' => $vehicle->load(['vehicleType', 'location'])
        ], 201);
    }

    public function show(Vehicle $vehicle)
    {
        return response()->json($vehicle->load(['vehicleType', 'location']));
    }

    public function update(VehicleRequest $request, Vehicle $vehicle)
    {
        $validated = $request->validated();
        $vehicle->update($validated);
        
        return response()->json([
            'message' => 'Vehicle updated successfully',
            'vehicle' => $vehicle->fresh(['vehicleType', 'location'])
        ]);
    }

    public function destroy(Vehicle $vehicle)
    {
        try {
            $vehicle->delete();
            return response()->json(['message' => 'Vehicle deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Cannot delete vehicle. It is referenced by other records.'], 400);
        }
    }

    public function availability(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'location_id' => 'nullable|exists:locations,id',
            'vehicle_type_id' => 'nullable|exists:vehicle_types,id'
        ]);
        
        $startDate = $validated['start_date'];
        $endDate = $validated['end_date'];
        
        $bookedVehicleIds = Booking::where(function($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<', $startDate)
                          ->where('end_date', '>', $endDate);
                    });
            })
            ->where('status', '!=', 'Rejected')
            ->pluck('vehicle_id')
            ->toArray();
        
        $availableVehicles = Vehicle::with(['vehicleType', 'location'])
            ->where('status', 'Available')
            ->whereNotIn('id', $bookedVehicleIds)
            ->when(isset($validated['location_id']), function($query) use ($validated) {
                $query->where('location_id', $validated['location_id']);
            })
            ->when(isset($validated['vehicle_type_id']), function($query) use ($validated) {
                $query->where('vehicle_type_id', $validated['vehicle_type_id']);
            })
            ->get();
        
        return response()->json($availableVehicles);
    }
}


// App/Http/Controllers/API/VehicleController.php