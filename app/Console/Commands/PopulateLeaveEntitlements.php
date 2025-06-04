<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\EmployeeLeaveEntitlement;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PopulateLeaveEntitlements extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leave:populate-entitlements {--year=} {--month=} {--employee=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate leave entitlements for employees';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $year = $this->option('year') ?: Carbon::now()->year;
        $month = $this->option('month');
        $employeeId = $this->option('employee');

        $this->info("Populating leave entitlements for year: {$year}");

        if ($employeeId) {
            $employees = User::where('id', $employeeId)->get();
        } else {
            $employees = User::where('is_active', true)
                ->where('has_official_contract', true)
                ->get();
        }

        if ($month) {
            $this->populateForMonth($employees, $year, $month);
        } else {
            // Populate for entire year
            for ($m = 1; $m <= 12; $m++) {
                $this->populateForMonth($employees, $year, $m);
            }
        }

        $this->info('Leave entitlements populated successfully!');
    }

    private function populateForMonth($employees, $year, $month)
    {
        $this->info("Processing month: {$month}/{$year}");

        foreach ($employees as $employee) {
            // Check if entitlement already exists
            $existing = EmployeeLeaveEntitlement::where('employee_id', $employee->id)
                ->where('year', $year)
                ->where('month', $month)
                ->first();

            if ($existing) {
                $this->line("Entitlement already exists for {$employee->name} ({$month}/{$year})");
                continue;
            }

            // Create new entitlement
            $entitlement = new EmployeeLeaveEntitlement();
            $entitlement->employee_id = $employee->id;
            $entitlement->year = $year;
            $entitlement->month = $month;
            $entitlement->monthly_allocation = 1.0; // 1 day per month
            $entitlement->days_earned = 1.0;
            $entitlement->days_used = 0.0;
            $entitlement->days_remaining = 1.0;
            $entitlement->max_monthly_usage = 2.0;
            $entitlement->has_official_contract = $employee->has_official_contract;
            $entitlement->is_probation = $employee->is_probation;
            $entitlement->carried_over_from_previous = 0.0; // No carry over policy
            $entitlement->forfeited_days = 0.0;
            $entitlement->is_calculated = true;
            $entitlement->calculated_at = Carbon::now();
            $entitlement->expires_at = Carbon::create($year, $month, 1)->endOfMonth();

            $entitlement->save();

            $this->line("Created entitlement for {$employee->name} ({$month}/{$year})");
        }
    }
}
