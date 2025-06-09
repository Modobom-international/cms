<?php

namespace App\Models;

use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceComplaint extends Model
{
    use LogsModelActivity;

    protected $fillable = [
        'employee_id',
        'attendance_id',
        'complaint_type',
        'description',
        'status',
        'reviewed_by',
        'admin_response',
        'reviewed_at',
        'proposed_changes'
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'proposed_changes' => 'array'
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function markAsUnderReview($reviewerId): void
    {
        $this->update([
            'status' => 'under_review',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now()
        ]);
    }

    public function markAsResolved($reviewerId, $adminResponse): void
    {
        $this->update([
            'status' => 'resolved',
            'reviewed_by' => $reviewerId,
            'admin_response' => $adminResponse,
            'reviewed_at' => now()
        ]);
    }

    public function markAsRejected($reviewerId, $adminResponse): void
    {
        $this->update([
            'status' => 'rejected',
            'reviewed_by' => $reviewerId,
            'admin_response' => $adminResponse,
            'reviewed_at' => now()
        ]);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeUnderReview($query)
    {
        return $query->where('status', 'under_review');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}
