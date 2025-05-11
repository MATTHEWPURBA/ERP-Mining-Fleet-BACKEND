<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        $locations = Location::when($request->search, function($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('address', 'like', "%{$search}%");
            })
            ->when($request->type, function($query, $type) {
                $query->where('type', $type);
            })
            ->orderBy('name')
            ->get();

        return response()->json($locations);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'type' => 'required|string|in:HQ,Branch,Mining',
            'coordinates' => 'nullable|string'
        ]);
        
        $location = Location::create($validated);
        
        return response()->json([
            'message' => 'Location created successfully',
            'location' => $location
        ], 201);
    }

    public function show(Location $location)
    {
        return response()->json($location);
    }

    public function update(Request $request, Location $location)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'type' => 'required|string|in:HQ,Branch,Mining',
            'coordinates' => 'nullable|string'
        ]);
        
        $location->update($validated);
        
        return response()->json([
            'message' => 'Location updated successfully',
            'location' => $location->fresh()
        ]);
    }

    public function destroy(Location $location)
    {
        try {
            $location->delete();
            return response()->json(['message' => 'Location deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Cannot delete location. It is referenced by other records.'], 400);
        }
    }
}


// app/Http/Controllers/API/LocationController.php