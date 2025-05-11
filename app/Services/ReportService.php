<?php

namespace App\Services;

use App\Exports\BookingExport;
use App\Exports\FuelExport;
use App\Exports\MaintenanceExport;
use App\Exports\UtilizationExport;
use App\Models\Booking;
use App\Models\FuelLog;
use App\Models\Maintenance;
use App\Models\Vehicle;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class ReportService
{
    /**
     * Generate booking report with flexible filtering
     * 
     * @param array $filters Filter criteria for bookings
     * @return array Report data including filters, counts, and records
     */
    public function generateBookingReport(array $filters): array
    {
        // Get filtered booking records
        $bookings = $this->getFilteredBookings($filters);
        
        // Return structured report data
        return [
            'filters' => $filters,
            'total_count' => $bookings->count(),
            'bookings' => $bookings->toArray()
        ];
    }
    
    /**
     * Generate Excel export of booking report
     * 
     * @param array $filters Filter criteria for bookings
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse Excel file download
     */
    public function generateBookingReportExcel(array $filters)
    {
        // Get filtered booking records
        $bookings = $this->getFilteredBookings($filters);
        
        // Generate filename with current date
        $fileName = 'booking_report_' . date('Y-m-d') . '.xlsx';
        
        // Return Excel download response using BookingExport class
        return Excel::download(new BookingExport($bookings), $fileName);
    }
    
    /**
     * Get filtered booking records based on criteria
     * 
     * @param array $filters Filter criteria
     * @return \Illuminate\Database\Eloquent\Collection Filtered booking records
     */
    private function getFilteredBookings(array $filters)
    {
        return Booking::with(['user', 'vehicle.vehicleType', 'vehicle.location', 'approvals.approver'])
            ->when(isset($filters['start_date']), function($query) use ($filters) {
                $query->where('start_date', '>=', $filters['start_date']);
            })
            ->when(isset($filters['end_date']), function($query) use ($filters) {
                $query->where('end_date', '<=', $filters['end_date']);
            })
            ->when(isset($filters['user_id']), function($query) use ($filters) {
                $query->where('user_id', $filters['user_id']);
            })
            ->when(isset($filters['status']), function($query) use ($filters) {
                $query->where('status', $filters['status']);
            })
            ->when(isset($filters['location_id']), function($query) use ($filters) {
                $query->whereHas('vehicle', function($q) use ($filters) {
                    $q->where('location_id', $filters['location_id']);
                });
            })
            ->when(isset($filters['vehicle_type_id']), function($query) use ($filters) {
                $query->whereHas('vehicle', function($q) use ($filters) {
                    $q->where('vehicle_type_id', $filters['vehicle_type_id']);
                });
            })
            ->orderBy('start_date', 'desc')
            ->get();
    }
    
    /**
     * Generate vehicle utilization report
     * 
     * @param array $filters Filter criteria for utilization analysis
     * @return array Utilization metrics and analysis
     */
    public function generateUtilizationReport(array $filters): array
    {
        // Get vehicles matching filter criteria
        $vehicles = $this->getFilteredVehicles($filters);
        
        // Parse date range from filters
        $startDate = Carbon::parse($filters['start_date']);
        $endDate = Carbon::parse($filters['end_date']);
        $totalDays = $endDate->diffInDays($startDate) + 1;
        
        // Analyze utilization for each vehicle
        $utilization = [];
        
        foreach ($vehicles as $vehicle) {
            // Get approved/completed bookings within date range
            $bookings = Booking::where('vehicle_id', $vehicle->id)
                ->whereIn('status', ['Approved', 'Completed'])
                ->where(function($query) use ($startDate, $endDate) {
                    // Complex date range logic for overlapping bookings
                    $query->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate])
                        ->orWhere(function($q) use ($startDate, $endDate) {
                            $q->where('start_date', '<', $startDate)
                              ->where('end_date', '>', $endDate);
                        });
                })
                ->get();
                
            // Calculate total booked hours
            $bookedHours = 0;
            foreach ($bookings as $booking) {
                // Calculate actual booking hours within the analyzed time range
                $bookingStart = max($startDate, Carbon::parse($booking->start_date));
                $bookingEnd = min($endDate, Carbon::parse($booking->end_date));
                $bookedHours += $bookingEnd->diffInHours($bookingStart);
            }
            
            // Calculate total available hours and utilization rate
            $totalHours = $totalDays * 24;
            $utilizationRate = ($totalHours > 0) ? ($bookedHours / $totalHours) * 100 : 0;
            
            // Add vehicle utilization metrics to results
            $utilization[] = [
                'vehicle' => $vehicle->toArray(),
                'total_bookings' => $bookings->count(),
                'booked_hours' => $bookedHours,
                'total_hours' => $totalHours,
                'utilization_rate' => round($utilizationRate, 2)
            ];
        }
        
        // Return structured report data
        return [
            'filters' => $filters,
            'total_vehicles' => count($utilization),
            'utilization' => $utilization
        ];
    }
    
    /**
     * Generate Excel export of utilization report
     * 
     * @param array $filters Filter criteria
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse Excel file download
     */
    public function generateUtilizationReportExcel(array $filters)
    {
        // Generate utilization report data
        $report = $this->generateUtilizationReport($filters);
        
        // Generate filename with current date
        $fileName = 'vehicle_utilization_report_' . date('Y-m-d') . '.xlsx';
        
        // Return Excel download using UtilizationExport class
        return Excel::download(new UtilizationExport($report['utilization']), $fileName);
    }
    
    /**
     * Get filtered vehicles based on criteria
     * 
     * @param array $filters Filter criteria
     * @return \Illuminate\Database\Eloquent\Collection Filtered vehicle records
     */
    private function getFilteredVehicles(array $filters)
    {
        return Vehicle::with(['vehicleType', 'location'])
            ->when(isset($filters['vehicle_id']), function($query) use ($filters) {
                $query->where('id', $filters['vehicle_id']);
            })
            ->when(isset($filters['location_id']), function($query) use ($filters) {
                $query->where('location_id', $filters['location_id']);
            })
            ->when(isset($filters['vehicle_type_id']), function($query) use ($filters) {
                $query->where('vehicle_type_id', $filters['vehicle_type_id']);
            })
            ->get();
    }
    
    /**
     * Generate maintenance report with summary statistics
     * 
     * @param array $filters Filter criteria
     * @return array Maintenance report data and statistics
     */
    public function generateMaintenanceReport(array $filters): array
    {
        // Get filtered maintenance records
        $maintenance = $this->getFilteredMaintenance($filters);
        
        // Calculate summary statistics
        $summary = [
            'total_records' => $maintenance->count(),
            'total_cost' => $maintenance->sum('cost'),
            'scheduled_count' => $maintenance->where('type', 'Scheduled')->count(),
            'unscheduled_count' => $maintenance->where('type', 'Unscheduled')->count(),
            'scheduled_cost' => $maintenance->where('type', 'Scheduled')->sum('cost'),
            'unscheduled_cost' => $maintenance->where('type', 'Unscheduled')->sum('cost')
        ];
        
        // Return structured report data
        return [
            'filters' => $filters,
            'summary' => $summary,
            'records' => $maintenance->toArray()
        ];
    }
    
    /**
     * Generate Excel export of maintenance report
     * 
     * @param array $filters Filter criteria
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse Excel file download
     */
    public function generateMaintenanceReportExcel(array $filters)
    {
        // Generate maintenance report data
        $report = $this->generateMaintenanceReport($filters);
        
        // Generate filename with current date
        $fileName = 'maintenance_report_' . date('Y-m-d') . '.xlsx';
        
        // Return Excel download using MaintenanceExport class
        return Excel::download(new MaintenanceExport($report), $fileName);
    }
    
    /**
     * Get filtered maintenance records based on criteria
     * 
     * @param array $filters Filter criteria
     * @return \Illuminate\Database\Eloquent\Collection Filtered maintenance records
     */
    private function getFilteredMaintenance(array $filters)
    {
        return Maintenance::with(['vehicle.vehicleType', 'vehicle.location'])
            ->when(isset($filters['start_date']), function($query) use ($filters) {
                $query->where('date', '>=', $filters['start_date']);
            })
            ->when(isset($filters['end_date']), function($query) use ($filters) {
                $query->where('date', '<=', $filters['end_date']);
            })
            ->when(isset($filters['vehicle_id']), function($query) use ($filters) {
                $query->where('vehicle_id', $filters['vehicle_id']);
            })
            ->when(isset($filters['type']), function($query) use ($filters) {
                $query->where('type', $filters['type']);
            })
            ->orderBy('date', 'desc')
            ->get();
    }
    
    /**
     * Generate fuel consumption report with monthly trends
     * 
     * @param array $filters Filter criteria
     * @return array Fuel consumption report with summary and trends
     */
    public function generateFuelReport(array $filters): array
    {
        // Get filtered fuel log records
        $fuelLogs = $this->getFilteredFuelLogs($filters);
        
        // Calculate summary statistics
        $summary = [
            'total_records' => $fuelLogs->count(),
            'total_liters' => $fuelLogs->sum('liters'),
            'total_cost' => $fuelLogs->sum('cost'),
            'average_price_per_liter' => $fuelLogs->sum('liters') > 0 ? 
                round($fuelLogs->sum('cost') / $fuelLogs->sum('liters'), 2) : 0
        ];
        
        // Group data by month for trend analysis
        $monthlyData = $fuelLogs->groupBy(function($item) {
            return Carbon::parse($item->date)->format('Y-m');
        });
        
        // Calculate monthly summaries
        $monthly = [];
        foreach ($monthlyData as $month => $logs) {
            $monthly[] = [
                'month' => $month,
                'records' => $logs->count(),
                'liters' => $logs->sum('liters'),
                'cost' => $logs->sum('cost')
            ];
        }
        
        // Return structured report data
        return [
            'filters' => $filters,
            'summary' => $summary,
            'monthly' => $monthly,
            'records' => $fuelLogs->toArray()
        ];
    }
    
    /**
     * Generate Excel export of fuel consumption report
     * 
     * @param array $filters Filter criteria
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse Excel file download
     */
    public function generateFuelReportExcel(array $filters)
    {
        // Generate fuel report data
        $report = $this->generateFuelReport($filters);
        
        // Generate filename with current date
        $fileName = 'fuel_consumption_report_' . date('Y-m-d') . '.xlsx';
        
        // Return Excel download using FuelExport class
        return Excel::download(new FuelExport($report), $fileName);
    }
    
    /**
     * Get filtered fuel log records based on criteria
     * 
     * @param array $filters Filter criteria
     * @return \Illuminate\Database\Eloquent\Collection Filtered fuel log records
     */
    private function getFilteredFuelLogs(array $filters)
    {
        return FuelLog::with(['vehicle.vehicleType', 'vehicle.location', 'creator'])
            ->when(isset($filters['start_date']), function($query) use ($filters) {
                $query->where('date', '>=', $filters['start_date']);
            })
            ->when(isset($filters['end_date']), function($query) use ($filters) {
                $query->where('date', '<=', $filters['end_date']);
            })
            ->when(isset($filters['vehicle_id']), function($query) use ($filters) {
                $query->where('vehicle_id', $filters['vehicle_id']);
            })
            ->when(isset($filters['location_id']), function($query) use ($filters) {
                $query->whereHas('vehicle', function($q) use ($filters) {
                    $q->where('location_id', $filters['location_id']);
                });
            })
            ->orderBy('date', 'desc')
            ->get();
    }
}

// App/services/ReportService.php