<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AttendanceComplaint;
use App\Models\Attendance;
use Carbon\Carbon;
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
        // Basic validation
        $request->validate([
            'attendance_id' => 'required_unless:complaint_type,missing_record|exists:attendances,id',
            'complaint_type' => 'required|in:incorrect_time,missing_record,technical_issue,other',
            'description' => 'required|string|min:10',
            'proposed_changes' => 'nullable|array'
        ]);

        // Additional validation for missing_record complaints
        if ($request->complaint_type === 'missing_record') {
            $request->validate([
                'proposed_changes' => 'required|array',
                'proposed_changes.date' => 'required|date',
                'proposed_changes.checkin_time' => 'nullable|string',
                'proposed_changes.checkout_time' => 'nullable|string',
                'proposed_changes.type' => 'nullable|in:full_day,half_day'
            ]);

            // Check if there's already an attendance record for this date
            $existingAttendance = Attendance::where('employee_id', Auth::id())
                ->where('date', $request->proposed_changes['date'])
                ->first();

            if ($existingAttendance) {
                return response()->json([
                    'message' => 'Attendance record already exists for this date'
                ], 400);
            }
        }

        // For non-missing_record complaints, check if attendance belongs to the authenticated user
        if ($request->attendance_id) {
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
        }

        $complaint = AttendanceComplaint::create([
            'employee_id' => Auth::id(),
            'attendance_id' => $request->attendance_id, // Can be null for missing_record complaints
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

    /**
     * Admin: Respond to complaint and update attendance if needed
     */
    public function respondToComplaint(Request $request, $id)
    {
        $complaint = AttendanceComplaint::with('attendance')->findOrFail($id);

        // Basic validation first
        $request->validate([
            'response_type' => 'required|in:approve,reject',
            'admin_response' => 'required|string|min:10',
        ]);

        // Additional validation based on complaint type and response type
        if ($request->response_type === 'approve') {
            if ($complaint->complaint_type === 'missing_record') {
                // For missing_record complaints, we need attendance_data to create new record
                $request->validate([
                    'attendance_data' => 'required|array',
                    'attendance_data.date' => 'required|date',
                    'attendance_data.checkin_time' => 'required|date',
                    'attendance_data.checkout_time' => 'nullable|date|after:attendance_data.checkin_time',
                    'attendance_data.type' => 'required|in:full_day,half_day',
                    'attendance_data.description' => 'nullable|string'
                ]);
            } else {
                // For existing attendance complaints, we need attendance_updates
                $request->validate([
                    'attendance_updates' => 'required|array',
                    'attendance_updates.checkin_time' => 'nullable|date',
                    'attendance_updates.checkout_time' => 'nullable|date',
                    'attendance_updates.type' => 'nullable|in:full_day,half_day',
                    'attendance_updates.description' => 'nullable|string'
                ]);
            }
        }

        // Start transaction to ensure both complaint and attendance are updated atomically
        \DB::beginTransaction();

        try {
            if ($request->response_type === 'approve') {
                if ($complaint->complaint_type === 'missing_record') {
                    // Create new attendance record for missing_record complaints
                    $attendanceData = $request->attendance_data;
                    $attendanceData['employee_id'] = $complaint->employee_id;

                    // Convert ISO date strings to proper format
                    if (isset($attendanceData['checkin_time'])) {
                        $attendanceData['checkin_time'] = Carbon::parse($attendanceData['checkin_time'])->format('Y-m-d H:i:s');
                    }
                    if (isset($attendanceData['checkout_time'])) {
                        $attendanceData['checkout_time'] = Carbon::parse($attendanceData['checkout_time'])->format('Y-m-d H:i:s');
                    }
                    if (isset($attendanceData['date'])) {
                        $attendanceData['date'] = Carbon::parse($attendanceData['date'])->format('Y-m-d');
                    }

                    // Check if attendance record already exists for this date
                    $existingAttendance = Attendance::where('employee_id', $complaint->employee_id)
                        ->where('date', $attendanceData['date'])
                        ->first();

                    if ($existingAttendance) {
                        \DB::rollBack();
                        return response()->json([
                            'message' => 'Attendance record already exists for this date'
                        ], 400);
                    }

                    $attendance = Attendance::create($attendanceData);

                    // Calculate work hours if both times are provided
                    if ($attendance->checkin_time && $attendance->checkout_time) {
                        $attendance->total_work_hours = $attendance->calculateWorkHours();
                        $attendance->save();
                    }

                    // Update attendance status
                    $attendance->updateStatus();

                    // Link the attendance record to the complaint
                    $complaint->update(['attendance_id' => $attendance->id]);
                } else {
                    // Update existing attendance record
                    if (!$complaint->attendance) {
                        \DB::rollBack();
                        return response()->json([
                            'message' => 'No attendance record found to update'
                        ], 400);
                    }

                    $attendance = $complaint->attendance;
                    $updates = array_filter($request->attendance_updates, function ($value) {
                        return $value !== null && $value !== '';
                    });

                    // Convert ISO date strings to proper format
                    if (isset($updates['checkin_time'])) {
                        $updates['checkin_time'] = Carbon::parse($updates['checkin_time'])->format('Y-m-d H:i:s');
                    }
                    if (isset($updates['checkout_time'])) {
                        $updates['checkout_time'] = Carbon::parse($updates['checkout_time'])->format('Y-m-d H:i:s');
                    }

                    if (!empty($updates)) {
                        $attendance->update($updates);

                        // Recalculate work hours if time was updated
                        if (isset($updates['checkin_time']) || isset($updates['checkout_time'])) {
                            $attendance->total_work_hours = $attendance->calculateWorkHours();
                            $attendance->save();
                        }

                        // Update attendance status
                        $attendance->updateStatus();
                    }
                }

                // Mark complaint as resolved
                $complaint->markAsResolved(Auth::id(), $request->admin_response);
            } else {
                // Mark complaint as rejected
                $complaint->markAsRejected(Auth::id(), $request->admin_response);
            }

            \DB::commit();

            return response()->json([
                'message' => 'Complaint ' . ($request->response_type === 'approve' ? 'approved' : 'rejected') . ' successfully',
                'data' => [
                    'complaint' => $complaint->fresh()->load(['attendance', 'employee:id,name,email', 'reviewer:id,name']),
                    'attendance' => $request->response_type === 'approve' ? $complaint->fresh()->attendance : null
                ]
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();

            return response()->json([
                'message' => 'Failed to process complaint response',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
