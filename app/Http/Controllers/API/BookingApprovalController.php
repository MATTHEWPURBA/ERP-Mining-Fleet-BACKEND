<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\BookingApproval;
use App\Services\BookingApprovalService;
use Illuminate\Http\Request;

class BookingApprovalController extends Controller
{

/**
 * Display a listing of all booking approvals.
 *
 * @param  \Illuminate\Http\Request  $request
 * @return \Illuminate\Http\Response
 */
public function index(Request $request)
{
    $user = $request->user();
    
    // Determine which approvals to show based on user role
    if ($user->role === 'Administrator') {
        // Admins can see all approval requests
        $approvals = BookingApproval::with(['booking.user', 'booking.vehicle.vehicleType'])
            ->orderBy('created_at', 'desc')
            ->get();
    } else {
        // Normal approvers only see their assigned approvals
        $approvals = BookingApproval::where('approver_id', $user->id)
            ->with(['booking.user', 'booking.vehicle.vehicleType'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
    
    return response()->json($approvals);
}




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



// backend/app/Http/Controllers/API/BookingApprovalController.php