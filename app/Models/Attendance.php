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

        $totalHours = $checkout->diffInMinutes($checkin) / 60;

        // Subtract lunch break for full day
        if ($this->type === 'full_day') {
            $totalHours -= 1.5;
        }

        return round($totalHours, 2);
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

        $requiredHours = $this->type === 'full_day' ? 8 : 4;

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
