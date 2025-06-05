<?php

namespace App\Models;

use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Attendance extends Model
{
    use LogsModelActivity;

    protected $fillable = [
        'employee_id',
        'date',
        'type',
        'checkin_time',
        'checkout_time',
        'total_work_hours',
        'status',
        'description',
        'branch_name'
    ];

    protected $casts = [
        'date' => 'date',
        'checkin_time' => 'datetime',
        'checkout_time' => 'datetime',
        'total_work_hours' => 'decimal:2'
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function complaints()
    {
        return $this->hasMany(AttendanceComplaint::class);
    }

    public function getActiveLeaveRequest()
    {
        return LeaveRequest::where('employee_id', $this->employee_id)
            ->approved()
            ->activeOn($this->date)
            ->first();
    }

    public function hasActiveLeave(): bool
    {
        return $this->getActiveLeaveRequest() !== null;
    }

    public function isOnRemoteWork(): bool
    {
        $activeLeave = $this->getActiveLeaveRequest();
        return $activeLeave && $activeLeave->request_type === 'remote_work';
    }

    public function isOnAbsence(): bool
    {
        $activeLeave = $this->getActiveLeaveRequest();
        return $activeLeave && $activeLeave->request_type === 'absence';
    }

    public function calculateWorkHours()
    {
        if (!$this->checkout_time) {
            return null;
        }

        $checkin = Carbon::parse($this->checkin_time);
        $checkout = Carbon::parse($this->checkout_time);

        $totalMinutes = $checkin->diffInMinutes($checkout);

        // Subtract lunch break if applicable
        $lunchBreakMinutes = $this->getLunchBreakMinutes();
        $totalMinutes -= $lunchBreakMinutes;

        return round($totalMinutes / 60, 2);
    }

    /**
     * Get lunch break minutes for this attendance record
     */
    private function getLunchBreakMinutes(): int
    {
        // Check if lunch break is enabled
        if (!config('attendance.lunch_break.enabled')) {
            return 0;
        }

        // If this is a half day and lunch break only applies to full days, return 0
        if ($this->type === 'half_day' && config('attendance.lunch_break.full_day_only')) {
            return 0;
        }

        // Check if the employee actually worked during lunch break hours
        if ($this->workedDuringLunchBreak()) {
            return $this->getLunchBreakDurationMinutes();
        }

        return 0;
    }

    /**
     * Get lunch break duration in minutes
     */
    private function getLunchBreakDurationMinutes(): int
    {
        $startTime = Carbon::createFromFormat('H:i', config('attendance.lunch_break.start_time'));
        $endTime = Carbon::createFromFormat('H:i', config('attendance.lunch_break.end_time'));

        return $startTime->diffInMinutes($endTime);
    }

    /**
     * Check if employee worked during the configured lunch break time
     */
    private function workedDuringLunchBreak(): bool
    {
        if (!$this->checkin_time || !$this->checkout_time) {
            return false;
        }

        $checkin = Carbon::parse($this->checkin_time);
        $checkout = Carbon::parse($this->checkout_time);

        // Create lunch break start and end times for the same date as checkin
        $lunchStart = $checkin->copy()->setTimeFromTimeString(config('attendance.lunch_break.start_time'));
        $lunchEnd = $checkin->copy()->setTimeFromTimeString(config('attendance.lunch_break.end_time'));

        // Check if work period overlaps with lunch break period
        return $checkin->lessThan($lunchEnd) && $checkout->greaterThan($lunchStart);
    }

    public function updateStatus()
    {
        if (!$this->total_work_hours) {
            // Check if employee has approved leave (absence)
            if ($this->isOnAbsence()) {
                $this->status = 'on_leave';
                return;
            }

            // Check if employee is on approved remote work
            if ($this->isOnRemoteWork()) {
                $this->status = 'remote_work';
                return;
            }

            $this->status = 'incomplete';
            return;
        }

        $requiredHours = config("attendance.required_hours.{$this->type}", 8);

        // If on remote work, consider it completed regardless of hours tracked
        if ($this->isOnRemoteWork()) {
            $this->status = 'remote_work';
            return;
        }

        $this->status = $this->total_work_hours >= $requiredHours ? 'completed' : 'incomplete';
    }

    public function getStatusDisplayAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'Completed',
            'incomplete' => 'Incomplete',
            'on_leave' => 'On Leave',
            'remote_work' => 'Remote Work',
            default => 'Unknown'
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'success',
            'incomplete' => 'warning',
            'on_leave' => 'info',
            'remote_work' => 'primary',
            default => 'secondary'
        };
    }
}
