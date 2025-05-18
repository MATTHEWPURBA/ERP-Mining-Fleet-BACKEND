<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\VehicleRequest;
use App\Models\Vehicle;
use App\Models\Booking;
use Illuminate\Http\Request;
use App\Services\VehicleService; // Import the service


class VehicleController extends Controller
{

    protected $vehicleService; // Add service property

        // Inject the service using dependency injection
        public function __construct(VehicleService $vehicleService)
        {
            $this->vehicleService = $vehicleService;
        }

        

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
        
        // Use the service instead of implementing the logic here
        $availableVehicles = $this->vehicleService->findAvailableVehicles($validated);
        
        return response()->json($availableVehicles);
    }



/**
 * Get bookings associated with a specific vehicle
 *
 * @param \App\Models\Vehicle $vehicle
 * @return \Illuminate\Http\JsonResponse
 */
public function getBookings(Vehicle $vehicle)
{
    // Using the relationship defined in the Vehicle model to get all bookings
    // Eager loading related user and approval data to minimize database queries
    $bookings = $vehicle->bookings()
        ->with(['user', 'approvals.approver']) // Eager load relationships for efficiency
        ->orderBy('start_date', 'desc') // Order by start date descending for latest bookings first
        ->get();
        
    return response()->json($bookings);
}

/**
 * Get maintenance records for a specific vehicle
 *
 * @param \App\Models\Vehicle $vehicle
 * @return \Illuminate\Http\JsonResponse
 */
public function getMaintenance(Vehicle $vehicle)
{
    // Using the relationship defined in the Vehicle model to fetch maintenance records
    $maintenance = $vehicle->maintenance()
        ->orderBy('date', 'desc') // Most recent maintenance first
        ->get();
        
    return response()->json($maintenance);
}

/**
 * Get fuel logs for a specific vehicle
 *
 * @param \App\Models\Vehicle $vehicle
 * @return \Illuminate\Http\JsonResponse
 */
public function getFuelLogs(Vehicle $vehicle)
{
    // Using the relationship defined in the Vehicle model to fetch fuel logs
    // Eager loading the creator (user who logged the fuel entry)
    $fuelLogs = $vehicle->fuelLogs()
        ->with('creator') // Eager load the user who created the log
        ->orderBy('date', 'desc') // Most recent fuel logs first
        ->get();
        
    return response()->json($fuelLogs);
}







}


// Backend/App/Http/Controllers/API/VehicleController.php