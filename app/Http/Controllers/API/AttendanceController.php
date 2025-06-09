<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function checkin(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:users,id',
            'type' => 'required|in:full_day,half_day'
        ]);

        // Check if attendance already exists for today
        $existingAttendance = Attendance::where('employee_id', $request->employee_id)
            ->where('date', Carbon::today())
            ->first();

        if ($existingAttendance) {
            return response()->json([
                'message' => 'Already checked in for today'
            ], 400);
        }

        // Check if employee has approved absence leave for today
        $activeAbsenceLeave = LeaveRequest::where('employee_id', $request->employee_id)
            ->approved()
            ->where('request_type', 'absence')
            ->activeOn(Carbon::today())
            ->first();

        if ($activeAbsenceLeave) {
            // Auto-create attendance record for leave
            $attendance = Attendance::create([
                'employee_id' => $request->employee_id,
                'date' => Carbon::today(),
                'type' => 'full_day',
                'status' => 'on_leave',
                'description' => 'On approved leave: ' . $activeAbsenceLeave->leave_type
            ]);

            return response()->json([
                'message' => 'Employee is on approved leave today',
                'data' => $attendance,
                'leave_info' => [
                    'leave_type' => $activeAbsenceLeave->leave_type,
                    'reason' => $activeAbsenceLeave->reason,
                    'approved_by' => $activeAbsenceLeave->approver->name ?? null
                ]
            ]);
        }

        // Check if employee has approved remote work for today
        $activeRemoteWork = LeaveRequest::where('employee_id', $request->employee_id)
            ->approved()
            ->where('request_type', 'remote_work')
            ->activeOn(Carbon::today())
            ->first();

        $attendance = Attendance::create([
            'employee_id' => $request->employee_id,
            'date' => Carbon::today(),
            'type' => $request->type,
            'checkin_time' => Carbon::now(),
            'status' => $activeRemoteWork ? 'remote_work' : 'incomplete',
            'description' => $activeRemoteWork ? 'Remote work: ' . $activeRemoteWork->reason : null
        ]);

        $response = [
            'message' => 'Check-in successful',
            'data' => $attendance
        ];

        if ($activeRemoteWork) {
            $response['remote_work_info'] = [
                'location' => $activeRemoteWork->remote_work_details['location'] ?? 'Not specified',
                'reason' => $activeRemoteWork->reason,
                'approved_by' => $activeRemoteWork->approver->name ?? null
            ];
        }

        return response()->json($response);
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:users,id'
        ]);

        $attendance = Attendance::where('employee_id', $request->employee_id)
            ->where('date', Carbon::today())
            ->first();

        if (!$attendance) {
            return response()->json([
                'message' => 'No check-in record found for today'
            ], 404);
        }

        if ($attendance->checkout_time) {
            return response()->json([
                'message' => 'Already checked out for today'
            ], 400);
        }

        // Don't allow checkout for leave status
        if ($attendance->status === 'on_leave') {
            return response()->json([
                'message' => 'Cannot checkout when on leave'
            ], 400);
        }

        $attendance->checkout_time = Carbon::now();
        $attendance->total_work_hours = $attendance->calculateWorkHours();
        $attendance->updateStatus();
        $attendance->save();

        return response()->json([
            'message' => 'Check-out successful',
            'data' => $attendance
        ]);
    }

    public function getTodayAttendance($employeeId)
    {
        $attendance = Attendance::where('employee_id', $employeeId)
            ->where('date', Carbon::today())
            ->first();

        if (!$attendance) {
            // Check if there's an active leave for today
            $activeLeave = LeaveRequest::where('employee_id', $employeeId)
                ->approved()
                ->activeOn(Carbon::today())
                ->first();

            if ($activeLeave) {
                return response()->json([
                    'message' => 'Employee is on leave today',
                    'leave_info' => [
                        'leave_type' => $activeLeave->leave_type,
                        'request_type' => $activeLeave->request_type,
                        'reason' => $activeLeave->reason,
                        'start_date' => $activeLeave->start_date,
                        'end_date' => $activeLeave->end_date
                    ]
                ]);
            }

            return response()->json([
                'message' => 'No attendance record found for today'
            ], 404);
        }

        $response = ['data' => $attendance];

        // Add leave information if applicable
        if (in_array($attendance->status, ['on_leave', 'remote_work'])) {
            $activeLeave = $attendance->getActiveLeaveRequest();
            if ($activeLeave) {
                $response['leave_info'] = [
                    'leave_type' => $activeLeave->leave_type,
                    'request_type' => $activeLeave->request_type,
                    'reason' => $activeLeave->reason,
                    'remote_work_details' => $activeLeave->remote_work_details
                ];
            }
        }

        return response()->json($response);
    }

    /**
     * Get attendance record for a specific date
     */
    public function getAttendanceByDate($employeeId, $date)
    {
        // Validate date format
        if (!Carbon::hasFormat($date, 'Y-m-d')) {
            return response()->json([
                'message' => 'Invalid date format. Please use YYYY-MM-DD format.'
            ], 400);
        }

        $attendance = Attendance::where('employee_id', $employeeId)
            ->where('date', $date)
            ->first();

        if (!$attendance) {
            // Check if there's an active leave for the date
            $activeLeave = LeaveRequest::where('employee_id', $employeeId)
                ->approved()
                ->activeOn($date)
                ->first();

            if ($activeLeave) {
                return response()->json([
                    'message' => 'Employee was on leave on this date',
                    'leave_info' => [
                        'leave_type' => $activeLeave->leave_type,
                        'request_type' => $activeLeave->request_type,
                        'reason' => $activeLeave->reason,
                        'start_date' => $activeLeave->start_date,
                        'end_date' => $activeLeave->end_date
                    ]
                ]);
            }

            return response()->json([
                'message' => 'No attendance record found for the specified date'
            ], 404);
        }

        $response = ['data' => $attendance];

        // Add leave information if applicable
        if (in_array($attendance->status, ['on_leave', 'remote_work'])) {
            $activeLeave = $attendance->getActiveLeaveRequest();
            if ($activeLeave) {
                $response['leave_info'] = [
                    'leave_type' => $activeLeave->leave_type,
                    'request_type' => $activeLeave->request_type,
                    'reason' => $activeLeave->reason,
                    'remote_work_details' => $activeLeave->remote_work_details
                ];
            }
        }

        return response()->json($response);
    }

    public function getAttendanceReport(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'type' => 'nullable|in:full_day,half_day',
            'branch_name' => 'nullable|string',
            'include_leave' => 'nullable|in:true,false,1,0'
        ]);

        // Convert string boolean to actual boolean
        $includeLeave = filter_var($request->include_leave, FILTER_VALIDATE_BOOLEAN);

        $query = Attendance::with('employee')
            ->where('date', $request->date);

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->branch_name) {
            $query->where('branch_name', $request->branch_name);
        }

        $attendances = $query->get()->map(function ($attendance) {
            $data = [
                'employee_id' => $attendance->employee_id,
                'employee_name' => $attendance->employee->name,
                'checkin_time' => $attendance->checkin_time ? $attendance->checkin_time->format('H:i') : null,
                'checkout_time' => $attendance->checkout_time ? $attendance->checkout_time->format('H:i') : null,
                'total_work_hours' => $attendance->total_work_hours,
                'status' => $attendance->status,
                'status_display' => $attendance->status_display,
                'branch_name' => $attendance->branch_name
            ];

            // Add leave information if applicable
            if (in_array($attendance->status, ['on_leave', 'remote_work'])) {
                $activeLeave = $attendance->getActiveLeaveRequest();
                if ($activeLeave) {
                    $data['leave_info'] = [
                        'leave_type' => $activeLeave->leave_type,
                        'request_type' => $activeLeave->request_type,
                        'reason' => $activeLeave->reason
                    ];
                }
            }

            return $data;
        });

        // If include_leave is true, also include employees who are on approved leave but don't have attendance records
        if ($includeLeave) {
            $attendanceEmployeeIds = $attendances->pluck('employee_id')->toArray();

            $leavesToday = LeaveRequest::with('employee')
                ->approved()
                ->activeOn($request->date)
                ->whereNotIn('employee_id', $attendanceEmployeeIds)
                ->get();

            foreach ($leavesToday as $leave) {
                $attendances->push([
                    'employee_id' => $leave->employee_id,
                    'employee_name' => $leave->employee->name,
                    'checkin_time' => null,
                    'checkout_time' => null,
                    'total_work_hours' => null,
                    'status' => $leave->request_type === 'absence' ? 'on_leave' : 'remote_work',
                    'status_display' => $leave->request_type === 'absence' ? 'On Leave' : 'Remote Work',
                    'branch_name' => null,
                    'leave_info' => [
                        'leave_type' => $leave->leave_type,
                        'request_type' => $leave->request_type,
                        'reason' => $leave->reason
                    ]
                ]);
            }
        }

        return response()->json($attendances);
    }

    /**
     * Admin: Add custom attendance record
     */
    public function addCustomAttendance(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'type' => 'required|in:full_day,half_day',
            'checkin_time' => 'required|date_format:Y-m-d H:i:s',
            'checkout_time' => 'required|date_format:Y-m-d H:i:s',
            'branch_name' => 'required|string',
            'description' => 'nullable|string'
        ]);

        // Check if attendance already exists for the date
        $existingAttendance = Attendance::where('employee_id', $request->employee_id)
            ->where('date', $request->date)
            ->first();

        if ($existingAttendance) {
            return response()->json([
                'message' => 'Attendance record already exists for this date'
            ], 400);
        }

        // Check if there's an active leave for this date
        $activeLeave = LeaveRequest::where('employee_id', $request->employee_id)
            ->approved()
            ->activeOn($request->date)
            ->first();

        // Create attendance record
        $attendance = Attendance::create([
            'employee_id' => $request->employee_id,
            'date' => $request->date,
            'type' => $request->type,
            'checkin_time' => $request->checkin_time,
            'checkout_time' => $request->checkout_time,
            'branch_name' => $request->branch_name,
            'description' => $request->description
        ]);

        // Calculate work hours and update status
        $attendance->total_work_hours = $attendance->calculateWorkHours();
        $attendance->updateStatus();
        $attendance->save();

        $response = [
            'message' => 'Custom attendance record added successfully',
            'data' => $attendance
        ];

        if ($activeLeave) {
            $response['warning'] = 'Employee had approved leave on this date';
            $response['leave_info'] = [
                'leave_type' => $activeLeave->leave_type,
                'request_type' => $activeLeave->request_type,
                'reason' => $activeLeave->reason
            ];
        }

        return response()->json($response, 201);
    }

    /**
     * Admin: Update custom attendance record
     */
    public function updateCustomAttendance(Request $request, $id)
    {
        $attendance = Attendance::findOrFail($id);

        $request->validate([
            'type' => 'nullable|in:full_day,half_day',
            'checkin_time' => 'nullable|date_format:Y-m-d H:i:s',
            'checkout_time' => 'nullable|date_format:Y-m-d H:i:s',
            'branch_name' => 'nullable|string',
            'description' => 'nullable|string'
        ]);

        // Update only provided fields
        if ($request->has('type')) {
            $attendance->type = $request->type;
        }
        if ($request->has('checkin_time')) {
            $attendance->checkin_time = $request->checkin_time;
        }
        if ($request->has('checkout_time')) {
            $attendance->checkout_time = $request->checkout_time;
        }
        if ($request->has('branch_name')) {
            $attendance->branch_name = $request->branch_name;
        }
        if ($request->has('description')) {
            $attendance->description = $request->description;
        }

        // Recalculate work hours and status
        $attendance->total_work_hours = $attendance->calculateWorkHours();
        $attendance->updateStatus();
        $attendance->save();

        return response()->json([
            'message' => 'Attendance record updated successfully',
            'data' => $attendance
        ]);
    }
}
