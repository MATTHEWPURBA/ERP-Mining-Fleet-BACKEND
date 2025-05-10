<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    private $reportService;
    
    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }
    
    public function bookingReport(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'user_id' => 'nullable|exists:users,id',
            'location_id' => 'nullable|exists:locations,id',
            'vehicle_type_id' => 'nullable|exists:vehicle_types,id',
            'status' => 'nullable|in:Pending,Approved,Rejected,Completed,Cancelled',
            'format' => 'nullable|in:json,excel'
        ]);
        
        $format = $validated['format'] ?? 'json';
        unset($validated['format']);
        
        if ($format === 'excel') {
            return $this->reportService->generateBookingReportExcel($validated);
        }
        
        $report = $this->reportService->generateBookingReport($validated);
        return response()->json($report);
    }
    
    public function vehicleUtilizationReport(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'location_id' => 'nullable|exists:locations,id',
            'vehicle_type_id' => 'nullable|exists:vehicle_types,id',
            'format' => 'nullable|in:json,excel'
        ]);
        
        $format = $validated['format'] ?? 'json';
        unset($validated['format']);
        
        if ($format === 'excel') {
            return $this->reportService->generateUtilizationReportExcel($validated);
        }
        
        $report = $this->reportService->generateUtilizationReport($validated);
        return response()->json($report);
    }
    
    public function maintenanceReport(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'type' => 'nullable|in:Scheduled,Unscheduled',
            'format' => 'nullable|in:json,excel'
        ]);
        
        $format = $validated['format'] ?? 'json';
        unset($validated['format']);
        
        if ($format === 'excel') {
            return $this->reportService->generateMaintenanceReportExcel($validated);
        }
        
        $report = $this->reportService->generateMaintenanceReport($validated);
        return response()->json($report);
    }
    
    public function fuelConsumptionReport(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'location_id' => 'nullable|exists:locations,id',
            'format' => 'nullable|in:json,excel'
        ]);
        
        $format = $validated['format'] ?? 'json';
        unset($validated['format']);
        
        if ($format === 'excel') {
            return $this->reportService->generateFuelReportExcel($validated);
        }
        
        $report = $this->reportService->generateFuelReport($validated);
        return response()->json($report);
    }
}
