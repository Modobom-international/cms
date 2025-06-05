<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class EmployeeLeaveEntitlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'year',
        'month',
        'monthly_allocation',
        'days_earned',
        'days_used',
        'days_remaining',
        'max_monthly_usage',
        'has_official_contract',
        'is_probation',
        'carried_over_from_previous',
        'forfeited_days',
        'is_calculated',
        'calculated_at',
        'expires_at'
    ];

    protected $casts = [
        'monthly_allocation' => 'decimal:1',
        'days_earned' => 'decimal:1',
        'days_used' => 'decimal:1',
        'days_remaining' => 'decimal:1',
        'max_monthly_usage' => 'decimal:1',
        'carried_over_from_previous' => 'decimal:1',
        'forfeited_days' => 'decimal:1',
        'has_official_contract' => 'boolean',
        'is_probation' => 'boolean',
        'is_calculated' => 'boolean',
        'calculated_at' => 'datetime',
        'expires_at' => 'datetime'
    ];

    /**
     * Get the employee that owns this entitlement
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * Calculate monthly entitlement based on company policy
     */
    public function calculateMonthlyEntitlement(): float
    {
        // Company policy: 1 day per month for official contract employees
        if ($this->has_official_contract && !$this->is_probation) {
            return 1.0;
        }

        return 0.0;
    }

    /**
     * Use leave days from this entitlement
     */
    public function useLeave(float $days): bool
    {
        if ($days > $this->days_remaining) {
            return false; // Not enough days available
        }

        if ($this->days_used + $days > $this->max_monthly_usage) {
            return false; // Exceeds monthly usage limit
        }

        $this->days_used += $days;
        $this->days_remaining -= $days;
        $this->save();

        return true;
    }

    /**
     * Return leave days to this entitlement (when leave is cancelled)
     */
    public function returnLeave(float $days): void
    {
        $this->days_used = max(0, $this->days_used - $days);
        $this->days_remaining = min($this->monthly_allocation, $this->days_remaining + $days);
        $this->save();
    }

    /**
     * Check if employee can use specified number of days
     */
    public function canUseLeave(float $days): bool
    {
        return $days <= $this->days_remaining &&
            ($this->days_used + $days) <= $this->max_monthly_usage;
    }

    /**
     * Get available days considering monthly usage limit
     */
    public function getAvailableDays(): float
    {
        return min(
            $this->days_remaining,
            $this->max_monthly_usage - $this->days_used
        );
    }

    /**
     * Mark entitlement as calculated
     */
    public function markAsCalculated(): void
    {
        $this->is_calculated = true;
        $this->calculated_at = now();
        $this->save();
    }

    /**
     * Check if entitlement has expired
     */
    public function hasExpired(): bool
    {
        return $this->expires_at && now()->isAfter($this->expires_at);
    }

    /**
     * Apply company policy to forfeit unused days at month end
     */
    public function applyNoCarryOverPolicy(): void
    {
        if ($this->hasExpired() && $this->days_remaining > 0) {
            $this->forfeited_days += $this->days_remaining;
            $this->days_remaining = 0;
            $this->save();
        }
    }

    /**
     * Get entitlement for specific employee and month
     */
    public static function getForEmployeeMonth(int $employeeId, int $year, int $month): ?self
    {
        return self::where('employee_id', $employeeId)
            ->where('year', $year)
            ->where('month', $month)
            ->first();
    }

    /**
     * Create or update entitlement for employee month
     */
    public static function createOrUpdateForMonth(int $employeeId, int $year, int $month, array $data = []): self
    {
        return self::updateOrCreate(
            [
                'employee_id' => $employeeId,
                'year' => $year,
                'month' => $month
            ],
            array_merge([
                'monthly_allocation' => 1.0,
                'days_earned' => 1.0,
                'days_used' => 0.0,
                'days_remaining' => 1.0,
                'max_monthly_usage' => 2.0,
                'has_official_contract' => true,
                'is_probation' => false,
                'carried_over_from_previous' => 0.0,
                'forfeited_days' => 0.0,
                'is_calculated' => true,
                'calculated_at' => now(),
                'expires_at' => now()->create($year, $month, 1)->endOfMonth()
            ], $data)
        );
    }

    /**
     * Scope for specific year
     */
    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Scope for specific month
     */
    public function scopeForMonth($query, int $month)
    {
        return $query->where('month', $month);
    }

    /**
     * Scope for calculated entitlements
     */
    public function scopeCalculated($query)
    {
        return $query->where('is_calculated', true);
    }

    /**
     * Scope for official contract employees
     */
    public function scopeOfficialContract($query)
    {
        return $query->where('has_official_contract', true);
    }

    /**
     * Scope for non-probation employees
     */
    public function scopeNonProbation($query)
    {
        return $query->where('is_probation', false);
    }
}
