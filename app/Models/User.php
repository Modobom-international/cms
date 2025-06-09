<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\LogsModelActivity;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, LogsModelActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'type_user',
        'role',
        'profile_photo_path',
        'employment_start_date',
        'has_official_contract',
        'is_probation',
        'department',
        'position',
        'hourly_rate',
        'daily_rate',
        'monthly_salary',
        'standard_work_hours_per_day',
        'work_days_per_week',
        'is_active'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'employment_start_date' => 'date',
            'has_official_contract' => 'boolean',
            'is_probation' => 'boolean',
            'hourly_rate' => 'decimal:2',
            'daily_rate' => 'decimal:2',
            'monthly_salary' => 'decimal:2',
            'standard_work_hours_per_day' => 'decimal:1',
            'is_active' => 'boolean'
        ];
    }

    /**
     * Get the boards owned by the user.
     */
    public function workspaces()
    {
        return $this->hasMany(Workspace::class, 'owner_id');
    }

    public function ownedBoards()
    {
        return $this->hasMany(Board::class, 'owner_id');
    }

    /**
     * Get the boards that the user is a member of.
     */
    public function boards()
    {
        return $this->belongsToMany(Board::class, 'board_users')
            ->withPivot('role') // Lưu vai trò của user trong board
            ->withTimestamps(); // ✅ Dùng withTimestamps() (có 's')
    }

    /**
     * Get the comments created by the user.
     */
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function deviceFingerprints()
    {
        return $this->hasMany(DeviceFingerprint::class);
    }

    public function cards()
    {
        return $this->belongsToMany(Card::class, 'card_users', 'user_id', 'card_id')->withTimestamps();
    }

    public function teams()
    {
        return $this->belongsTo(Team::class, 'user_team', 'user_id', 'team_id');
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'user_permission', 'user_id', 'permission_id');
    }

    public function apiKeys()
    {
        return $this->hasMany(ApiKey::class);
    }

    // New relationships for leave and attendance
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'employee_id');
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class, 'employee_id');
    }

    public function leaveEntitlements()
    {
        return $this->hasMany(EmployeeLeaveEntitlement::class, 'employee_id');
    }

    public function attendanceComplaints()
    {
        return $this->hasMany(AttendanceComplaint::class, 'employee_id');
    }

    // Approved leave requests
    public function approvedLeaveRequests()
    {
        return $this->hasMany(LeaveRequest::class, 'approved_by');
    }

    /**
     * Get leave entitlement for specific month
     */
    public function getLeaveEntitlementForMonth(int $year, int $month): ?EmployeeLeaveEntitlement
    {
        return $this->leaveEntitlements()
            ->forYearMonth($year, $month)
            ->first();
    }

    /**
     * Get current month leave entitlement
     */
    public function getCurrentMonthLeaveEntitlement(): ?EmployeeLeaveEntitlement
    {
        $now = now();
        return $this->getLeaveEntitlementForMonth($now->year, $now->month);
    }

    /**
     * Calculate daily salary rate
     */
    public function calculateDailyRate(): float
    {
        if ($this->daily_rate) {
            return $this->daily_rate;
        }

        if ($this->monthly_salary && $this->work_days_per_week) {
            // Assuming 4.33 weeks per month on average
            $workDaysPerMonth = $this->work_days_per_week * 4.33;
            return $this->monthly_salary / $workDaysPerMonth;
        }

        if ($this->hourly_rate && $this->standard_work_hours_per_day) {
            return $this->hourly_rate * $this->standard_work_hours_per_day;
        }

        return 0;
    }

    /**
     * Calculate hourly rate
     */
    public function calculateHourlyRate(): float
    {
        if ($this->hourly_rate) {
            return $this->hourly_rate;
        }

        if ($this->daily_rate && $this->standard_work_hours_per_day) {
            return $this->daily_rate / $this->standard_work_hours_per_day;
        }

        if ($this->monthly_salary && $this->work_days_per_week && $this->standard_work_hours_per_day) {
            $workDaysPerMonth = $this->work_days_per_week * 4.33;
            $workHoursPerMonth = $workDaysPerMonth * $this->standard_work_hours_per_day;
            return $this->monthly_salary / $workHoursPerMonth;
        }

        return 0;
    }

    /**
     * Check if employee is eligible for paid leave
     */
    public function isEligibleForPaidLeave(): bool
    {
        return $this->has_official_contract && !$this->is_probation && $this->is_active;
    }

    /**
     * Get employee work schedule info
     */
    public function getWorkScheduleInfo(): array
    {
        return [
            'standard_work_hours_per_day' => $this->standard_work_hours_per_day ?? 8.0,
            'work_days_per_week' => $this->work_days_per_week ?? 5,
            'saturday_remote_work' => true, // Company policy: Saturdays are remote work
            'hourly_rate' => $this->calculateHourlyRate(),
            'daily_rate' => $this->calculateDailyRate()
        ];
    }

    // Query Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithOfficialContract($query)
    {
        return $query->where('has_official_contract', true);
    }

    public function scopeNotProbation($query)
    {
        return $query->where('is_probation', false);
    }

    public function scopeEligibleForLeave($query)
    {
        return $query->active()
            ->withOfficialContract()
            ->notProbation();
    }

    public function scopeByDepartment($query, string $department)
    {
        return $query->where('department', $department);
    }
}
