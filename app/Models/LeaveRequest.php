<?php

namespace App\Models;

use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class LeaveRequest extends Model
{
    use LogsModelActivity;

    protected $fillable = [
        'employee_id',
        'leave_type',
        'request_type',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'is_full_day',
        'reason',
        'additional_notes',
        'status',
        'approved_by',
        'approval_notes',
        'approved_at',
        'remote_work_details',
        'total_days'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'approved_at' => 'datetime',
        'remote_work_details' => 'array',
        'is_full_day' => 'boolean',
        'total_days' => 'decimal:2'
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approve($approverId, $approvalNotes = null): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approverId,
            'approval_notes' => $approvalNotes,
            'approved_at' => now()
        ]);
    }

    public function reject($approverId, $approvalNotes): void
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $approverId,
            'approval_notes' => $approvalNotes,
            'approved_at' => now()
        ]);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function calculateTotalDays(): float
    {
        $startDate = Carbon::parse($this->start_date);
        $endDate = Carbon::parse($this->end_date);

        if ($this->is_full_day) {
            return $endDate->diffInDays($startDate) + 1;
        } else {
            // For partial days, calculate based on hours
            $startTime = Carbon::parse($this->start_time);
            $endTime = Carbon::parse($this->end_time);
            $hoursRequested = $endTime->diffInHours($startTime);
            return round($hoursRequested / 8, 2); // Assuming 8-hour workday
        }
    }

    public function isActive(): bool
    {
        if ($this->status !== 'approved') {
            return false;
        }

        $today = Carbon::today();
        return $today->between($this->start_date, $this->end_date);
    }

    public function isActiveOnDate($date): bool
    {
        if ($this->status !== 'approved') {
            return false;
        }

        $checkDate = Carbon::parse($date);
        return $checkDate->between($this->start_date, $this->end_date);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'cancelled' => 'secondary',
            default => 'secondary'
        };
    }

    // Query Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeAbsence($query)
    {
        return $query->where('request_type', 'absence');
    }

    public function scopeRemoteWork($query)
    {
        return $query->where('request_type', 'remote_work');
    }

    public function scopeActiveOn($query, $date)
    {
        return $query->where('status', 'approved')
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date);
    }

    public function scopeOverlapping($query, $startDate, $endDate, $excludeId = null)
    {
        $query = $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
                ->orWhereBetween('end_date', [$startDate, $endDate])
                ->orWhere(function ($q2) use ($startDate, $endDate) {
                    $q2->where('start_date', '<=', $startDate)
                        ->where('end_date', '>=', $endDate);
                });
        });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($leaveRequest) {
            $leaveRequest->total_days = $leaveRequest->calculateTotalDays();
        });

        static::updating(function ($leaveRequest) {
            if ($leaveRequest->isDirty(['start_date', 'end_date', 'start_time', 'end_time', 'is_full_day'])) {
                $leaveRequest->total_days = $leaveRequest->calculateTotalDays();
            }
        });
    }
}
