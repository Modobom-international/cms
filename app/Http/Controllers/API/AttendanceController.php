<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AttendanceController extends Controller
{

    public function __construct()
    {
        
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'server_id' => 'required|string',
                'network' => 'required|array',
            ]);

            foreach ($data['network'] as $device) {
                $employee = Employee::where('mac_address', $device['mac'])->first();

                $attendance = Attendance::where('server_id', $data['server_id'])
                    ->where('mac', $device['mac'])
                    ->whereNull('last_seen')
                    ->first();

                if ($attendance) {
                    $attendance->update(['last_seen' => $device['timestamp']]);
                } else {
                    Attendance::create([
                        'server_id' => $data['server_id'],
                        'ip' => $device['ip'],
                        'mac' => $device['mac'],
                        'first_seen' => $device['timestamp'],
                        'last_seen' => $device['timestamp'],
                        'device_name' => $device['device_name'] ?? null,
                        'employee_id' => $employee ? $employee->id : null,
                    ]); 
                }
            }

            return response()->json(['status' => 'success', 'message' => 'Attendance data received'], 200);
        } catch (\Exception $e) {
            Log::error('Attendance data error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }
}
