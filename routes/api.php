<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BookingApprovalController;
use App\Http\Controllers\API\BookingController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\FuelLogController;
use App\Http\Controllers\API\LocationController;
use App\Http\Controllers\API\MaintenanceController;
use App\Http\Controllers\API\ReportController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\VehicleController;
use App\Http\Controllers\API\VehicleTypeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/test', function() {
    return response()->json(['message' => 'API is working!']);
});
// Public routes
Route::post('/login', [AuthController::class, 'login']);


// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // Dashboard routes
    Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);
    Route::get('/dashboard/vehicle-distribution', [DashboardController::class, 'getVehicleDistribution']);
    Route::get('/dashboard/recent-bookings', [DashboardController::class, 'getRecentBookings']);
    Route::get('/dashboard/vehicle-utilization', [DashboardController::class, 'getVehicleUtilization']);
    Route::get('/dashboard/fuel-consumption', [DashboardController::class, 'getFuelConsumption']);
    Route::get('/dashboard/user-bookings', [DashboardController::class, 'getUserBookings']);
    Route::get('/dashboard/user-approvals', [DashboardController::class, 'getUserPendingApprovals']);
    Route::get('/dashboard/user-upcoming-bookings', [DashboardController::class, 'getUserUpcomingBookings']);
    Route::get('/dashboard/user-vehicles', [DashboardController::class, 'getUserRecentlyUsedVehicles']);
    
    // User routes
    Route::apiResource('users', UserController::class);
    
    // Location routes
    Route::apiResource('locations', LocationController::class);
    
    // Vehicle Type routes
    Route::apiResource('vehicle-types', VehicleTypeController::class);
    
    // Vehicle routes
    Route::apiResource('vehicles', VehicleController::class);
    Route::get('/vehicles/{vehicle}/bookings', [VehicleController::class, 'getBookings']);
    Route::get('/vehicles/{vehicle}/maintenance', [VehicleController::class, 'getMaintenance']);
    Route::get('/vehicles/{vehicle}/fuel-logs', [VehicleController::class, 'getFuelLogs']);
    Route::get('/vehicles/availability', [VehicleController::class, 'availability']);

    
    // Booking routes
    Route::apiResource('bookings', BookingController::class);
    Route::put('/bookings/{booking}/complete', [BookingController::class, 'complete']);
    Route::put('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);
    
    // Booking Approval routes
    Route::apiResource('booking-approvals', BookingApprovalController::class)->only(['index', 'show', 'update']);
    Route::put('/booking-approvals/{approval}/approve', [BookingApprovalController::class, 'approve']);
    Route::put('/booking-approvals/{approval}/reject', [BookingApprovalController::class, 'reject']);
    
    // Maintenance routes
    Route::apiResource('maintenance', MaintenanceController::class);
    
    // Fuel Log routes
    Route::apiResource('fuel-logs', FuelLogController::class);
    
    // Report routes
    Route::post('/reports/bookings', [ReportController::class, 'bookingReport']);
    Route::post('/reports/bookings/excel', [ReportController::class, 'bookingReportExcel']);
    Route::post('/reports/utilization', [ReportController::class, 'utilizationReport']);
    Route::post('/reports/utilization/excel', [ReportController::class, 'utilizationReportExcel']);
    Route::post('/reports/maintenance', [ReportController::class, 'maintenanceReport']);
    Route::post('/reports/maintenance/excel', [ReportController::class, 'maintenanceReportExcel']);
    Route::post('/reports/fuel', [ReportController::class, 'fuelReport']);
    Route::post('/reports/fuel/excel', [ReportController::class, 'fuelReportExcel']);
});

// backend/routes/api.php