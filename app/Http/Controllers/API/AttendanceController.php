<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
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

        $attendance = Attendance::create([
            'employee_id' => $request->employee_id,
            'date' => Carbon::today(),
            'type' => $request->type,
            'checkin_time' => Carbon::now(),
            'status' => 'incomplete',
            'branch_name' => $request->attributes->get('branch_name')
        ]);

        return response()->json([
            'message' => 'Check-in successful',
            'data' => $attendance
        ]);
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

        $attendance->checkout_time = Carbon::now();
        $attendance->total_work_hours = $attendance->calculateWorkHours();
        $attendance->updateStatus();
        $attendance->branch_name = $request->attributes->get('branch_name');
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
            return response()->json([
                'message' => 'No attendance record found for today'
            ], 404);
        }

        return response()->json($attendance);
    }

    public function getAttendanceReport(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'type' => 'nullable|in:full_day,half_day',
            'branch_name' => 'nullable|string'
        ]);

        $query = Attendance::with('employee')
            ->where('date', $request->date);

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->branch_name) {
            $query->where('branch_name', $request->branch_name);
        }

        $attendances = $query->get()->map(function ($attendance) {
            return [
                'employee_id' => $attendance->employee_id,
                'employee_name' => $attendance->employee->name,
                'checkin_time' => $attendance->checkin_time->format('H:i'),
                'checkout_time' => $attendance->checkout_time ? $attendance->checkout_time->format('H:i') : null,
                'total_work_hours' => $attendance->total_work_hours,
                'status' => $attendance->status,
                'branch_name' => $attendance->branch_name
            ];
        });

        return response()->json($attendances);
    }
}