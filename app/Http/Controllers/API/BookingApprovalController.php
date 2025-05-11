<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\BookingApproval;
use App\Services\BookingApprovalService;
use Illuminate\Http\Request;

class BookingApprovalController extends Controller
{
    private $bookingApprovalService;
    
    public function __construct(BookingApprovalService $bookingApprovalService)
    {
        $this->bookingApprovalService = $bookingApprovalService;
    }
    
    public function pending(Request $request)
    {
        $user = $request->user();
        
        $pendingApprovals = BookingApproval::where('approver_id', $user->id)
            ->where('status', 'Pending')
            ->with(['booking.user', 'booking.vehicle.vehicleType'])
            ->get();
        
        return response()->json($pendingApprovals);
    }
    
    public function approve(Request $request, BookingApproval $approval)
    {
        $this->authorize('approve', $approval);
        
        $comments = $request->input('comments');
        $result = $this->bookingApprovalService->approveBooking($approval, $comments);
        
        return response()->json([
            'message' => 'Booking approved successfully',
            'approval' => $result
        ]);
    }
    
    public function reject(Request $request, BookingApproval $approval)
    {
        $this->authorize('reject', $approval);
        
        $comments = $request->input('comments');
        $result = $this->bookingApprovalService->rejectBooking($approval, $comments);
        
        return response()->json([
            'message' => 'Booking rejected',
            'approval' => $result
        ]);
    }
}



// app/Http/Controllers/API/BookingApprovalController.php