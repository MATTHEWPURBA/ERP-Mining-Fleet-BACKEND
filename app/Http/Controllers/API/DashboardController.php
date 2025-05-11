<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    private $dashboardService;
    
    /**
     * Create a new controller instance.
     *
     * @param DashboardService $dashboardService
     */
    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }
    
    /**
     * Get dashboard statistics
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats()
    {
        $data = [
            'vehicle_count' => $this->dashboardService->getVehicleCount(),
            'booking_count' => $this->dashboardService->getBookingCount(),
            'pending_approvals' => $this->dashboardService->getPendingApprovalCount(),
            'upcoming_maintenance' => $this->dashboardService->getUpcomingMaintenanceCount(),
        ];
        
        return response()->json($data);
    }
    
    /**
     * Get vehicle distribution data for dashboard charts
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getVehicleDistribution()
    {
        $data = [
            'vehicle_status' => $this->dashboardService->getVehicleStatusDistribution(),
            'vehicle_types' => $this->dashboardService->getVehicleTypeDistribution(),
        ];
        
        return response()->json($data);
    }
    
    /**
     * Get recent bookings for dashboard display
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRecentBookings()
    {
        return response()->json($this->dashboardService->getRecentBookings());
    }
    
    /**
     * Get vehicle utilization data for dashboard charts
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getVehicleUtilization()
    {
        return response()->json($this->dashboardService->getVehicleUtilization());
    }
    
    /**
     * Get fuel consumption data for dashboard charts
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFuelConsumption()
    {
        return response()->json($this->dashboardService->getFuelConsumptionData());
    }
    
    /**
     * Get current user's bookings for user dashboard
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserBookings(Request $request)
    {
        return response()->json($this->dashboardService->getUserBookings($request->user()->id));
    }
    
    /**
     * Get pending approvals for current user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserPendingApprovals(Request $request)
    {
        return response()->json($this->dashboardService->getUserPendingApprovals($request->user()->id));
    }
    
    /**
     * Get upcoming bookings for current user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserUpcomingBookings(Request $request)
    {
        return response()->json($this->dashboardService->getUserUpcomingBookings($request->user()->id));
    }
    
    /**
     * Get recently used vehicles for current user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserRecentlyUsedVehicles(Request $request)
    {
        return response()->json($this->dashboardService->getUserRecentlyUsedVehicles($request->user()->id));
    }
}




// app/Http/Controllers/API/DashboardController.php