<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicHoliday extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'original_date',
        'observed_date',
        'year',
        'is_recurring',
        'adjustment_rule',
        'holiday_type',
        'is_paid',
        'affects_salary',
        'requires_coverage',
        'coverage_requirements',
        'overtime_multiplier',
        'description',
        'is_active',
        'affected_departments'
    ];

    protected $casts = [
        'original_date' => 'date',
        'observed_date' => 'date',
        'is_recurring' => 'boolean',
        'is_paid' => 'boolean',
        'affects_salary' => 'boolean',
        'requires_coverage' => 'boolean',
        'is_active' => 'boolean',
        'overtime_multiplier' => 'decimal:2',
        'affected_departments' => 'array'
    ];

    /**
     * Adjust holiday date if it falls on weekend
     */
    public function adjustForWeekend(): void
    {
        $originalDate = Carbon::parse($this->original_date);

        // If holiday falls on weekend, adjust based on company rule
        if ($originalDate->isWeekend()) {
            switch ($this->adjustment_rule) {
                case 'previous_workday':
                    $this->observed_date = $originalDate->copy()->previousWeekday();
                    break;

                case 'next_workday':
                    $this->observed_date = $originalDate->copy()->nextWeekday();
                    break;

                case 'company_decision':
                    // Default to next workday, but can be manually adjusted
                    $this->observed_date = $originalDate->copy()->nextWeekday();
                    break;

                default:
                    $this->observed_date = $this->original_date;
            }
        } else {
            $this->observed_date = $this->original_date;
        }
    }

    /**
     * Check if a given date is a public holiday
     */
    public static function isPublicHoliday(Carbon $date): bool
    {
        return self::where('observed_date', $date->format('Y-m-d'))
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get public holiday for a specific date
     */
    public static function getHolidayForDate(Carbon $date): ?self
    {
        return self::where('observed_date', $date->format('Y-m-d'))
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get holidays for a specific year
     */
    public static function getHolidaysForYear(int $year)
    {
        return self::where('year', $year)
            ->where('is_active', true)
            ->orderBy('observed_date')
            ->get();
    }

    /**
     * Get holidays for a specific month
     */
    public static function getHolidaysForMonth(int $year, int $month)
    {
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        return self::whereBetween('observed_date', [$startDate, $endDate])
            ->where('is_active', true)
            ->orderBy('observed_date')
            ->get();
    }

    /**
     * Get upcoming holidays
     */
    public static function getUpcomingHolidays(int $limit = 5)
    {
        return self::where('observed_date', '>=', Carbon::today())
            ->where('is_active', true)
            ->orderBy('observed_date')
            ->limit($limit)
            ->get();
    }

    /**
     * Check if this holiday requires coverage
     */
    public function requiresCoverage(): bool
    {
        return $this->requires_coverage;
    }

    /**
     * Get the overtime multiplier for working on this holiday
     */
    public function getOvertimeMultiplier(): float
    {
        return $this->overtime_multiplier ?? 2.0;
    }

    /**
     * Check if this holiday affects a specific department
     */
    public function affectsDepartment(string $department): bool
    {
        if (empty($this->affected_departments)) {
            return true; // Affects all departments if none specified
        }

        return in_array($department, $this->affected_departments);
    }

    /**
     * Scope for active holidays
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for holidays by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('holiday_type', $type);
    }

    /**
     * Scope for recurring holidays
     */
    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    /**
     * Scope for paid holidays
     */
    public function scopePaid($query)
    {
        return $query->where('is_paid', true);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($holiday) {
            $holiday->adjustForWeekend();
        });
    }
}
