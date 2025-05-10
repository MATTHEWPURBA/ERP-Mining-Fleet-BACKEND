<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    private $dashboardService;
    
    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }
    
    public function index()
    {
        $data = [
            'vehicle_count' => $this->dashboardService->getVehicleCount(),
            'booking_count' => $this->dashboardService->getBookingCount(),
            'pending_approvals' => $this->dashboardService->getPendingApprovalCount(),
            'upcoming_maintenance' => $this->dashboardService->getUpcomingMaintenanceCount(),
            'vehicle_status' => $this->dashboardService->getVehicleStatusDistribution(),
            'vehicle_types' => $this->dashboardService->getVehicleTypeDistribution(),
            'recent_bookings' => $this->dashboardService->getRecentBookings(),
            'vehicle_utilization' => $this->dashboardService->getVehicleUtilization(),
            'fuel_consumption' => $this->dashboardService->getFuelConsumptionData(),
        ];
        
        return response()->json($data);
    }
    
    public function userDashboard(Request $request)
    {
        $user = $request->user();
        
        $data = [
            'my_bookings' => $this->dashboardService->getUserBookings($user->id),
            'pending_approvals' => $this->dashboardService->getUserPendingApprovals($user->id),
            'upcoming_bookings' => $this->dashboardService->getUserUpcomingBookings($user->id),
            'recently_used_vehicles' => $this->dashboardService->getUserRecentlyUsedVehicles($user->id),
        ];
        
        return response()->json($data);
    }
}

