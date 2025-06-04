<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class LeaveRequestController extends Controller
{
    /**
     * Employee: Create a new leave request
     */
    public function store(Request $request)
    {
        $request->validate([
            'leave_type' => 'required|in:sick,vacation,personal,maternity,paternity,emergency,remote_work,other',
            'request_type' => 'required|in:absence,remote_work',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'is_full_day' => 'boolean',
            'reason' => 'required|string|min:10',
            'additional_notes' => 'nullable|string',
            'remote_work_details' => 'nullable|array',
            'remote_work_details.location' => 'nullable|string',
            'remote_work_details.equipment_needed' => 'nullable|string',
            'remote_work_details.contact_number' => 'nullable|string'
        ]);

        // Validate time fields for partial day requests
        if (!$request->is_full_day) {
            $request->validate([
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time'
            ]);
        }

        // Check for overlapping requests
        $overlappingRequests = LeaveRequest::where('employee_id', Auth::id())
            ->overlapping($request->start_date, $request->end_date)
            ->whereIn('status', ['pending', 'approved'])
            ->count();

        if ($overlappingRequests > 0) {
            return response()->json([
                'message' => 'You already have a pending or approved request for the selected dates'
            ], 400);
        }

        // Create the leave request
        $leaveRequest = LeaveRequest::create([
            'employee_id' => Auth::id(),
            'leave_type' => $request->leave_type,
            'request_type' => $request->request_type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'is_full_day' => $request->is_full_day ?? true,
            'reason' => $request->reason,
            'additional_notes' => $request->additional_notes,
            'remote_work_details' => $request->remote_work_details
        ]);

        return response()->json([
            'message' => 'Leave request submitted successfully',
            'data' => $leaveRequest->load(['employee:id,name,email'])
        ], 201);
    }

    /**
     * Employee: Get own leave requests
     */
    public function index(Request $request)
    {
        $request->validate([
            'status' => 'nullable|in:pending,approved,rejected,cancelled',
            'request_type' => 'nullable|in:absence,remote_work',
            'leave_type' => 'nullable|in:sick,vacation,personal,maternity,paternity,emergency,remote_work,other',
            'year' => 'nullable|integer|min:2020|max:2030',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        $query = LeaveRequest::with(['approver:id,name'])
            ->where('employee_id', Auth::id());

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->request_type) {
            $query->where('request_type', $request->request_type);
        }

        if ($request->leave_type) {
            $query->where('leave_type', $request->leave_type);
        }

        if ($request->year) {
            $query->whereYear('start_date', $request->year);
        }

        $leaveRequests = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($leaveRequests);
    }

    /**
     * Employee: Get specific leave request
     */
    public function show($id)
    {
        $leaveRequest = LeaveRequest::with(['employee:id,name,email', 'approver:id,name'])
            ->where('employee_id', Auth::id())
            ->findOrFail($id);

        return response()->json($leaveRequest);
    }

    /**
     * Employee: Update leave request (only if pending)
     */
    public function update(Request $request, $id)
    {
        $leaveRequest = LeaveRequest::where('employee_id', Auth::id())
            ->findOrFail($id);

        if ($leaveRequest->status !== 'pending') {
            return response()->json([
                'message' => 'Cannot update leave request that is not pending'
            ], 400);
        }

        $request->validate([
            'leave_type' => 'nullable|in:sick,vacation,personal,maternity,paternity,emergency,remote_work,other',
            'request_type' => 'nullable|in:absence,remote_work',
            'start_date' => 'nullable|date|after_or_equal:today',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'is_full_day' => 'nullable|boolean',
            'reason' => 'nullable|string|min:10',
            'additional_notes' => 'nullable|string',
            'remote_work_details' => 'nullable|array'
        ]);

        // Check for overlapping requests if dates are being updated
        if ($request->has(['start_date', 'end_date'])) {
            $startDate = $request->start_date ?? $leaveRequest->start_date;
            $endDate = $request->end_date ?? $leaveRequest->end_date;

            $overlappingRequests = LeaveRequest::where('employee_id', Auth::id())
                ->overlapping($startDate, $endDate, $leaveRequest->id)
                ->whereIn('status', ['pending', 'approved'])
                ->count();

            if ($overlappingRequests > 0) {
                return response()->json([
                    'message' => 'You already have a pending or approved request for the selected dates'
                ], 400);
            }
        }

        $leaveRequest->update($request->only([
            'leave_type',
            'request_type',
            'start_date',
            'end_date',
            'start_time',
            'end_time',
            'is_full_day',
            'reason',
            'additional_notes',
            'remote_work_details'
        ]));

        return response()->json([
            'message' => 'Leave request updated successfully',
            'data' => $leaveRequest->fresh()->load(['employee:id,name,email'])
        ]);
    }

    /**
     * Employee: Cancel leave request
     */
    public function cancel($id)
    {
        $leaveRequest = LeaveRequest::where('employee_id', Auth::id())
            ->findOrFail($id);

        if (!in_array($leaveRequest->status, ['pending', 'approved'])) {
            return response()->json([
                'message' => 'Cannot cancel this leave request'
            ], 400);
        }

        $leaveRequest->cancel();

        return response()->json([
            'message' => 'Leave request cancelled successfully',
            'data' => $leaveRequest->fresh()
        ]);
    }

    /**
     * Admin: Get all leave requests
     */
    public function adminIndex(Request $request)
    {
        $request->validate([
            'status' => 'nullable|in:pending,approved,rejected,cancelled',
            'request_type' => 'nullable|in:absence,remote_work',
            'leave_type' => 'nullable|in:sick,vacation,personal,maternity,paternity,emergency,remote_work,other',
            'employee_id' => 'nullable|exists:users,id',
            'year' => 'nullable|integer|min:2020|max:2030',
            'month' => 'nullable|integer|min:1|max:12',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        $query = LeaveRequest::with(['employee:id,name,email', 'approver:id,name']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->request_type) {
            $query->where('request_type', $request->request_type);
        }

        if ($request->leave_type) {
            $query->where('leave_type', $request->leave_type);
        }

        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->year) {
            $query->whereYear('start_date', $request->year);
        }

        if ($request->month) {
            $query->whereMonth('start_date', $request->month);
        }

        $leaveRequests = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($leaveRequests);
    }

    /**
     * Admin: Get specific leave request
     */
    public function adminShow($id)
    {
        $leaveRequest = LeaveRequest::with(['employee:id,name,email', 'approver:id,name'])
            ->findOrFail($id);

        return response()->json($leaveRequest);
    }

    /**
     * Admin: Approve/Reject leave request
     */
    public function updateStatus(Request $request, $id)
    {
        $leaveRequest = LeaveRequest::findOrFail($id);

        if ($leaveRequest->status !== 'pending') {
            return response()->json([
                'message' => 'Can only approve or reject pending requests'
            ], 400);
        }

        $request->validate([
            'status' => 'required|in:approved,rejected',
            'approval_notes' => 'nullable|string'
        ]);

        switch ($request->status) {
            case 'approved':
                $leaveRequest->approve(Auth::id(), $request->approval_notes);
                $message = 'Leave request approved successfully';
                break;
            case 'rejected':
                $leaveRequest->reject(Auth::id(), $request->approval_notes);
                $message = 'Leave request rejected successfully';
                break;
        }

        return response()->json([
            'message' => $message,
            'data' => $leaveRequest->fresh()->load(['employee:id,name,email', 'approver:id,name'])
        ]);
    }

    /**
     * Admin: Get leave request statistics
     */
    public function getStatistics(Request $request)
    {
        $request->validate([
            'year' => 'nullable|integer|min:2020|max:2030',
            'month' => 'nullable|integer|min:1|max:12'
        ]);

        $query = LeaveRequest::query();

        if ($request->year) {
            $query->whereYear('start_date', $request->year);
        }

        if ($request->month) {
            $query->whereMonth('start_date', $request->month);
        }

        $stats = [
            'total' => $query->count(),
            'pending' => $query->clone()->pending()->count(),
            'approved' => $query->clone()->approved()->count(),
            'rejected' => $query->clone()->rejected()->count(),
            'cancelled' => $query->clone()->cancelled()->count(),
            'by_type' => [
                'absence' => $query->clone()->absence()->count(),
                'remote_work' => $query->clone()->remoteWork()->count()
            ],
            'by_leave_type' => $query->clone()
                ->selectRaw('leave_type, count(*) as count')
                ->groupBy('leave_type')
                ->pluck('count', 'leave_type'),
            'total_days_requested' => $query->clone()->sum('total_days')
        ];

        return response()->json($stats);
    }

    /**
     * Get current active leave requests for a date
     */
    public function getActiveLeaves(Request $request)
    {
        $request->validate([
            'date' => 'nullable|date'
        ]);

        $date = $request->date ?? Carbon::today()->toDateString();

        $activeLeaves = LeaveRequest::with(['employee:id,name,email'])
            ->activeOn($date)
            ->get();

        return response()->json([
            'date' => $date,
            'active_leaves' => $activeLeaves
        ]);
    }

    /**
     * Employee: Get leave balance/summary
     */
    public function getLeaveBalance()
    {
        $currentYear = Carbon::now()->year;

        $totalUsed = LeaveRequest::where('employee_id', Auth::id())
            ->approved()
            ->whereYear('start_date', $currentYear)
            ->sum('total_days');

        $byType = LeaveRequest::where('employee_id', Auth::id())
            ->approved()
            ->whereYear('start_date', $currentYear)
            ->selectRaw('leave_type, sum(total_days) as total_days')
            ->groupBy('leave_type')
            ->pluck('total_days', 'leave_type');

        $pending = LeaveRequest::where('employee_id', Auth::id())
            ->pending()
            ->sum('total_days');

        return response()->json([
            'year' => $currentYear,
            'total_used' => $totalUsed,
            'pending_days' => $pending,
            'by_leave_type' => $byType,
            // You can add company-specific leave entitlements here
            'annual_entitlement' => 25, // This should come from company policy or user settings
            'remaining' => max(0, 25 - $totalUsed - $pending)
        ]);
    }
}
