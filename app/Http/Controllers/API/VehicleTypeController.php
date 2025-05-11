<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\VehicleType;
use Illuminate\Http\Request;

class VehicleTypeController extends Controller
{
    public function index()
    {
        $vehicleTypes = VehicleType::orderBy('name')->get();
        return response()->json($vehicleTypes);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'description' => 'nullable|string'
        ]);
        
        $vehicleType = VehicleType::create($validated);
        
        return response()->json([
            'message' => 'Vehicle type created successfully',
            'vehicle_type' => $vehicleType
        ], 201);
    }

    public function show(VehicleType $vehicleType)
    {
        return response()->json($vehicleType);
    }

    public function update(Request $request, VehicleType $vehicleType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'description' => 'nullable|string'
        ]);
        
        $vehicleType->update($validated);
        
        return response()->json([
            'message' => 'Vehicle type updated successfully',
            'vehicle_type' => $vehicleType->fresh()
        ]);
    }

    public function destroy(VehicleType $vehicleType)
    {
        try {
            $vehicleType->delete();
            return response()->json(['message' => 'Vehicle type deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Cannot delete vehicle type. It is referenced by other records.'], 400);
        }
    }
}


// app/Http/Controllers/API/VehicleTypeController.php