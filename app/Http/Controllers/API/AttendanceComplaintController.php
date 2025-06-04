<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AttendanceComplaint;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AttendanceComplaintController extends Controller
{
    /**
     * Employee: Create a new complaint
     */
    public function store(Request $request)
    {
        $request->validate([
            'attendance_id' => 'required|exists:attendances,id',
            'complaint_type' => 'required|in:incorrect_time,missing_record,technical_issue,other',
            'description' => 'required|string|min:10',
            'proposed_changes' => 'nullable|array'
        ]);

        // Check if attendance belongs to the authenticated user
        $attendance = Attendance::findOrFail($request->attendance_id);
        if ($attendance->employee_id !== Auth::id()) {
            return response()->json([
                'message' => 'You can only file complaints for your own attendance records'
            ], 403);
        }

        // Check if there's already a pending complaint for this attendance
        $existingComplaint = AttendanceComplaint::where('attendance_id', $request->attendance_id)
            ->whereIn('status', ['pending', 'under_review'])
            ->first();

        if ($existingComplaint) {
            return response()->json([
                'message' => 'There is already a pending complaint for this attendance record'
            ], 400);
        }

        $complaint = AttendanceComplaint::create([
            'employee_id' => Auth::id(),
            'attendance_id' => $request->attendance_id,
            'complaint_type' => $request->complaint_type,
            'description' => $request->description,
            'proposed_changes' => $request->proposed_changes
        ]);

        return response()->json([
            'message' => 'Complaint submitted successfully',
            'data' => $complaint->load(['attendance', 'employee:id,name,email'])
        ], 201);
    }

    /**
     * Employee: Get own complaints
     */
    public function index(Request $request)
    {
        $request->validate([
            'status' => 'nullable|in:pending,under_review,resolved,rejected',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        $query = AttendanceComplaint::with(['attendance', 'reviewer:id,name'])
            ->where('employee_id', Auth::id());

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $complaints = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($complaints);
    }

    /**
     * Employee: Get specific complaint
     */
    public function show($id)
    {
        $complaint = AttendanceComplaint::with(['attendance', 'employee:id,name,email', 'reviewer:id,name'])
            ->where('employee_id', Auth::id())
            ->findOrFail($id);

        return response()->json($complaint);
    }

    /**
     * Employee: Update complaint (only if pending)
     */
    public function update(Request $request, $id)
    {
        $complaint = AttendanceComplaint::where('employee_id', Auth::id())
            ->findOrFail($id);

        if ($complaint->status !== 'pending') {
            return response()->json([
                'message' => 'Cannot update complaint that is not pending'
            ], 400);
        }

        $request->validate([
            'complaint_type' => 'nullable|in:incorrect_time,missing_record,technical_issue,other',
            'description' => 'nullable|string|min:10',
            'proposed_changes' => 'nullable|array'
        ]);

        $complaint->update($request->only(['complaint_type', 'description', 'proposed_changes']));

        return response()->json([
            'message' => 'Complaint updated successfully',
            'data' => $complaint->load(['attendance', 'employee:id,name,email'])
        ]);
    }

    /**
     * Admin: Get all complaints
     */
    public function adminIndex(Request $request)
    {
        $request->validate([
            'status' => 'nullable|in:pending,under_review,resolved,rejected',
            'employee_id' => 'nullable|exists:users,id',
            'complaint_type' => 'nullable|in:incorrect_time,missing_record,technical_issue,other',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        $query = AttendanceComplaint::with(['attendance', 'employee:id,name,email', 'reviewer:id,name']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->complaint_type) {
            $query->where('complaint_type', $request->complaint_type);
        }

        $complaints = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($complaints);
    }

    /**
     * Admin: Get specific complaint
     */
    public function adminShow($id)
    {
        $complaint = AttendanceComplaint::with(['attendance', 'employee:id,name,email', 'reviewer:id,name'])
            ->findOrFail($id);

        return response()->json($complaint);
    }

    /**
     * Admin: Update complaint status
     */
    public function updateStatus(Request $request, $id)
    {
        $complaint = AttendanceComplaint::findOrFail($id);

        $request->validate([
            'status' => 'required|in:under_review,resolved,rejected',
            'admin_response' => 'required_if:status,resolved,rejected|string'
        ]);

        switch ($request->status) {
            case 'under_review':
                $complaint->markAsUnderReview(Auth::id());
                break;
            case 'resolved':
                $complaint->markAsResolved(Auth::id(), $request->admin_response);
                break;
            case 'rejected':
                $complaint->markAsRejected(Auth::id(), $request->admin_response);
                break;
        }

        return response()->json([
            'message' => 'Complaint status updated successfully',
            'data' => $complaint->fresh()->load(['attendance', 'employee:id,name,email', 'reviewer:id,name'])
        ]);
    }

    /**
     * Admin: Get complaint statistics
     */
    public function getStatistics()
    {
        $stats = [
            'total' => AttendanceComplaint::count(),
            'pending' => AttendanceComplaint::pending()->count(),
            'under_review' => AttendanceComplaint::underReview()->count(),
            'resolved' => AttendanceComplaint::resolved()->count(),
            'rejected' => AttendanceComplaint::rejected()->count(),
            'by_type' => AttendanceComplaint::selectRaw('complaint_type, count(*) as count')
                ->groupBy('complaint_type')
                ->pluck('count', 'complaint_type')
        ];

        return response()->json($stats);
    }
}
