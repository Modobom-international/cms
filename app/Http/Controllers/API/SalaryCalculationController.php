<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\EmployeeLeaveEntitlement;
use App\Models\PublicHoliday;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalaryCalculationController extends Controller
{
    /**
     * Calculate salary for a specific employee for a given month
     */
    public function calculateMonthlySalary(Request $request, $employeeId)
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|between:1,12',
        ]);

        $employee = User::findOrFail($employeeId);
        $year = $request->year;
        $month = $request->month;

        // Get the first and last day of the month
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        $salaryData = $this->calculateEmployeeSalary($employee, $startDate, $endDate);

        return response()->json($salaryData);
    }

    /**
     * Calculate salaries for all employees for a given month
     */
    public function calculateAllSalaries(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|between:1,12',
            'department' => 'nullable|string',
            'employee_ids' => 'nullable|array',
            'employee_ids.*' => 'integer|exists:users,id'
        ]);

        $year = $request->year;
        $month = $request->month;
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        $query = User::query()->where('is_active', true);

        if ($request->department) {
            $query->where('department', $request->department);
        }

        if ($request->employee_ids) {
            $query->whereIn('id', $request->employee_ids);
        }

        $employees = $query->get();
        $salaryData = [];

        foreach ($employees as $employee) {
            $salaryData[] = $this->calculateEmployeeSalary($employee, $startDate, $endDate);
        }

        return response()->json([
            'year' => $year,
            'month' => $month,
            'employees' => $salaryData,
            'total_employees' => count($salaryData),
            'total_gross_salary' => array_sum(array_column($salaryData, 'gross_salary')),
            'total_deductions' => array_sum(array_column($salaryData, 'total_deductions')),
            'total_net_salary' => array_sum(array_column($salaryData, 'net_salary'))
        ]);
    }

    /**
     * Get attendance summary for salary calculation
     */
    public function getAttendanceSummary(Request $request, $employeeId)
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|between:1,12',
        ]);

        $employee = User::findOrFail($employeeId);
        $year = $request->year;
        $month = $request->month;

        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        $summary = $this->getEmployeeAttendanceSummary($employee, $startDate, $endDate);

        return response()->json($summary);
    }

    /**
     * Get leave summary for salary calculation
     */
    public function getLeaveSummary(Request $request, $employeeId)
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|between:1,12',
        ]);

        $employee = User::findOrFail($employeeId);
        $year = $request->year;
        $month = $request->month;

        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        $summary = $this->getEmployeeLeaveSummary($employee, $startDate, $endDate);

        return response()->json($summary);
    }

    /**
     * Main method to calculate employee salary
     */
    private function calculateEmployeeSalary(User $employee, Carbon $startDate, Carbon $endDate)
    {
        // Get attendance summary
        $attendanceSummary = $this->getEmployeeAttendanceSummary($employee, $startDate, $endDate);

        // Get leave summary
        $leaveSummary = $this->getEmployeeLeaveSummary($employee, $startDate, $endDate);

        // Get public holidays in the period
        $publicHolidays = PublicHoliday::whereBetween('observed_date', [$startDate, $endDate])
            ->where('is_active', true)
            ->count();

        // Calculate working days in month (excluding weekends and holidays)
        $totalWorkingDays = $this->calculateWorkingDaysInPeriod($startDate, $endDate, $employee);

        // Calculate base salary components
        $baseSalary = $this->calculateBaseSalary($employee, $attendanceSummary, $totalWorkingDays);

        // Calculate leave adjustments
        $leaveAdjustments = $this->calculateLeaveAdjustments($employee, $leaveSummary);

        // Calculate overtime
        $overtimePayment = $this->calculateOvertime($employee, $attendanceSummary);

        // Calculate deductions
        $deductions = $this->calculateDeductions($employee, $attendanceSummary, $leaveSummary);

        // Calculate bonuses (Saturday work, holiday work, etc.)
        $bonuses = $this->calculateBonuses($employee, $attendanceSummary);

        $grossSalary = $baseSalary + $leaveAdjustments + $overtimePayment + $bonuses;
        $totalDeductions = array_sum($deductions);
        $netSalary = $grossSalary - $totalDeductions;

        return [
            'employee_id' => $employee->id,
            'employee_name' => $employee->name,
            'employee_email' => $employee->email,
            'department' => $employee->department,
            'position' => $employee->position,
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'month' => $startDate->month,
                'year' => $startDate->year
            ],
            'salary_components' => [
                'base_salary' => $baseSalary,
                'leave_adjustments' => $leaveAdjustments,
                'overtime_payment' => $overtimePayment,
                'bonuses' => $bonuses,
                'gross_salary' => $grossSalary,
                'deductions' => $deductions,
                'total_deductions' => $totalDeductions,
                'net_salary' => $netSalary
            ],
            'attendance_summary' => $attendanceSummary,
            'leave_summary' => $leaveSummary,
            'working_days' => [
                'total_working_days' => $totalWorkingDays,
                'public_holidays' => $publicHolidays,
                'days_worked' => $attendanceSummary['days_worked'],
                'days_on_leave' => $leaveSummary['total_leave_days'],
                'absent_days' => $attendanceSummary['absent_days']
            ]
        ];
    }

    /**
     * Get employee attendance summary for the period
     */
    private function getEmployeeAttendanceSummary(User $employee, Carbon $startDate, Carbon $endDate)
    {
        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $summary = [
            'total_records' => $attendances->count(),
            'days_worked' => 0,
            'days_on_leave' => 0,
            'days_remote_work' => 0,
            'absent_days' => 0,
            'total_hours_worked' => 0,
            'overtime_hours' => 0,
            'saturday_work_days' => 0,
            'holiday_work_days' => 0,
            'late_arrivals' => 0,
            'early_departures' => 0
        ];

        foreach ($attendances as $attendance) {
            switch ($attendance->status) {
                case 'completed':
                    $summary['days_worked']++;
                    $summary['total_hours_worked'] += $attendance->worked_hours ?? 0;

                    // Check for overtime
                    $standardHours = $employee->standard_work_hours_per_day;
                    if (($attendance->worked_hours ?? 0) > $standardHours) {
                        $summary['overtime_hours'] += ($attendance->worked_hours - $standardHours);
                    }

                    // Check if it's Saturday work
                    if (Carbon::parse($attendance->date)->isSaturday()) {
                        $summary['saturday_work_days']++;
                    }

                    // Check for late arrival/early departure
                    if ($attendance->is_late) {
                        $summary['late_arrivals']++;
                    }
                    break;

                case 'on_leave':
                    $summary['days_on_leave']++;
                    break;

                case 'remote_work':
                    $summary['days_remote_work']++;
                    $summary['total_hours_worked'] += $attendance->worked_hours ?? $employee->standard_work_hours_per_day;
                    break;

                case 'incomplete':
                default:
                    $summary['absent_days']++;
                    break;
            }
        }

        // Calculate holiday work days
        $publicHolidays = PublicHoliday::whereBetween('observed_date', [$startDate, $endDate])
            ->where('is_active', true)
            ->pluck('observed_date')
            ->map(function ($date) {
                return Carbon::parse($date)->format('Y-m-d');
            });

        $summary['holiday_work_days'] = $attendances->filter(function ($attendance) use ($publicHolidays) {
            return $attendance->status === 'completed' &&
                $publicHolidays->contains($attendance->date);
        })->count();

        return $summary;
    }

    /**
     * Get employee leave summary for the period
     */
    private function getEmployeeLeaveSummary(User $employee, Carbon $startDate, Carbon $endDate)
    {
        $leaveRequests = LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            })
            ->get();

        $summary = [
            'total_leave_requests' => $leaveRequests->count(),
            'total_leave_days' => 0,
            'paid_leave_days' => 0,
            'unpaid_leave_days' => 0,
            'sick_leave_days' => 0,
            'vacation_days' => 0,
            'personal_leave_days' => 0,
            'remote_work_days' => 0,
            'leave_breakdown' => []
        ];

        foreach ($leaveRequests as $leave) {
            // Calculate days within the period
            $leaveStart = Carbon::parse($leave->start_date)->max($startDate);
            $leaveEnd = Carbon::parse($leave->end_date)->min($endDate);
            $daysInPeriod = $leaveStart->diffInDays($leaveEnd) + 1;

            $summary['total_leave_days'] += $daysInPeriod;

            // Categorize by type
            switch ($leave->leave_type) {
                case 'sick':
                    $summary['sick_leave_days'] += $daysInPeriod;
                    break;
                case 'vacation':
                    $summary['vacation_days'] += $daysInPeriod;
                    break;
                case 'personal':
                    $summary['personal_leave_days'] += $daysInPeriod;
                    break;
                case 'remote_work':
                    $summary['remote_work_days'] += $daysInPeriod;
                    break;
            }

            // Determine if paid or unpaid based on employee entitlements
            if ($employee->has_official_contract && !$employee->is_probation) {
                $entitlement = EmployeeLeaveEntitlement::where('employee_id', $employee->id)
                    ->where('year', $startDate->year)
                    ->where('month', $startDate->month)
                    ->first();

                if ($entitlement && $entitlement->days_remaining >= $daysInPeriod) {
                    $summary['paid_leave_days'] += $daysInPeriod;
                } else {
                    $summary['unpaid_leave_days'] += $daysInPeriod;
                }
            } else {
                $summary['unpaid_leave_days'] += $daysInPeriod;
            }

            $summary['leave_breakdown'][] = [
                'leave_id' => $leave->id,
                'leave_type' => $leave->leave_type,
                'request_type' => $leave->request_type,
                'start_date' => $leave->start_date,
                'end_date' => $leave->end_date,
                'days_in_period' => $daysInPeriod,
                'is_paid' => $employee->has_official_contract && !$employee->is_probation
            ];
        }

        return $summary;
    }

    /**
     * Calculate working days in period excluding weekends and holidays
     */
    private function calculateWorkingDaysInPeriod(Carbon $startDate, Carbon $endDate, User $employee)
    {
        $current = $startDate->copy();
        $workingDays = 0;

        $publicHolidays = PublicHoliday::whereBetween('observed_date', [$startDate, $endDate])
            ->where('is_active', true)
            ->pluck('observed_date')
            ->map(function ($date) {
                return Carbon::parse($date)->format('Y-m-d');
            });

        while ($current <= $endDate) {
            // Skip weekends (Saturday and Sunday)
            if (!$current->isWeekend()) {
                // Skip public holidays
                if (!$publicHolidays->contains($current->format('Y-m-d'))) {
                    $workingDays++;
                }
            }
            $current->addDay();
        }

        return $workingDays;
    }

    /**
     * Calculate base salary
     */
    private function calculateBaseSalary(User $employee, array $attendanceSummary, int $totalWorkingDays)
    {
        if ($employee->monthly_salary) {
            // Monthly salary employees get full salary regardless of attendance
            return $employee->monthly_salary;
        } elseif ($employee->daily_rate) {
            // Daily rate employees get paid per day worked
            return $employee->daily_rate * $attendanceSummary['days_worked'];
        } elseif ($employee->hourly_rate) {
            // Hourly employees get paid per hour worked
            return $employee->hourly_rate * $attendanceSummary['total_hours_worked'];
        }

        return 0;
    }

    /**
     * Calculate leave adjustments
     */
    private function calculateLeaveAdjustments(User $employee, array $leaveSummary)
    {
        $adjustments = 0;

        // For daily/hourly employees, deduct unpaid leave
        if (!$employee->monthly_salary && $leaveSummary['unpaid_leave_days'] > 0) {
            if ($employee->daily_rate) {
                $adjustments -= $employee->daily_rate * $leaveSummary['unpaid_leave_days'];
            } elseif ($employee->hourly_rate) {
                $adjustments -= $employee->hourly_rate * $employee->standard_work_hours_per_day * $leaveSummary['unpaid_leave_days'];
            }
        }

        return $adjustments;
    }

    /**
     * Calculate overtime payment
     */
    private function calculateOvertime(User $employee, array $attendanceSummary)
    {
        if ($attendanceSummary['overtime_hours'] <= 0) {
            return 0;
        }

        $hourlyRate = $employee->hourly_rate;
        if (!$hourlyRate) {
            // Calculate hourly rate from daily or monthly salary
            if ($employee->daily_rate) {
                $hourlyRate = $employee->daily_rate / $employee->standard_work_hours_per_day;
            } elseif ($employee->monthly_salary) {
                $hourlyRate = $employee->monthly_salary / (22 * $employee->standard_work_hours_per_day); // Assuming 22 working days per month
            }
        }

        // Overtime is typically paid at 1.5x rate
        return $hourlyRate * $attendanceSummary['overtime_hours'] * 1.5;
    }

    /**
     * Calculate bonuses (Saturday work, holiday work, etc.)
     */
    private function calculateBonuses(User $employee, array $attendanceSummary)
    {
        $bonuses = 0;

        // Saturday work bonus (double pay)
        if ($attendanceSummary['saturday_work_days'] > 0) {
            $dailyRate = $this->getDailyRate($employee);
            $bonuses += $dailyRate * $attendanceSummary['saturday_work_days']; // Additional payment for Saturday work
        }

        // Holiday work bonus (double pay)
        if ($attendanceSummary['holiday_work_days'] > 0) {
            $dailyRate = $this->getDailyRate($employee);
            $bonuses += $dailyRate * $attendanceSummary['holiday_work_days']; // Additional payment for holiday work
        }

        return $bonuses;
    }

    /**
     * Calculate deductions
     */
    private function calculateDeductions(User $employee, array $attendanceSummary, array $leaveSummary)
    {
        $deductions = [];

        // Deduct for excessive late arrivals (more than 3 times)
        if ($attendanceSummary['late_arrivals'] > 3) {
            $dailyRate = $this->getDailyRate($employee);
            $deductions['late_arrival_penalty'] = ($attendanceSummary['late_arrivals'] - 3) * ($dailyRate * 0.1); // 10% of daily rate per late arrival
        }

        // Deduct for unauthorized absences
        if ($attendanceSummary['absent_days'] > 0) {
            $dailyRate = $this->getDailyRate($employee);
            $deductions['unauthorized_absence'] = $attendanceSummary['absent_days'] * $dailyRate;
        }

        return $deductions;
    }

    /**
     * Get daily rate for an employee
     */
    private function getDailyRate(User $employee)
    {
        if ($employee->daily_rate) {
            return $employee->daily_rate;
        } elseif ($employee->monthly_salary) {
            return $employee->monthly_salary / 22; // Assuming 22 working days per month
        } elseif ($employee->hourly_rate) {
            return $employee->hourly_rate * $employee->standard_work_hours_per_day;
        }

        return 0;
    }
}
