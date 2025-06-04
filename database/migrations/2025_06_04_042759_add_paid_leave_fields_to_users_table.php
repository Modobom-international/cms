<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Annual leave entitlements
            $table->decimal('annual_leave_days', 5, 2)->default(25.00)->after('email'); // Total annual leave days
            $table->decimal('sick_leave_days', 5, 2)->default(12.00)->after('annual_leave_days'); // Annual sick leave days
            $table->decimal('personal_leave_days', 5, 2)->default(5.00)->after('sick_leave_days'); // Personal leave days

            // Monthly accrual rates (how many days earned per month)
            $table->decimal('monthly_leave_accrual', 5, 2)->default(2.08)->after('personal_leave_days'); // 25/12 = 2.08 days per month
            $table->decimal('monthly_sick_accrual', 5, 2)->default(1.00)->after('monthly_leave_accrual'); // 1 day per month

            // Current balances (remaining days)
            $table->decimal('current_leave_balance', 5, 2)->default(0.00)->after('monthly_sick_accrual');
            $table->decimal('current_sick_balance', 5, 2)->default(0.00)->after('current_leave_balance');
            $table->decimal('current_personal_balance', 5, 2)->default(0.00)->after('current_sick_balance');

            // Carried over from previous year
            $table->decimal('carried_over_days', 5, 2)->default(0.00)->after('current_personal_balance');
            $table->integer('max_carry_over_days')->default(5)->after('carried_over_days'); // Maximum days that can be carried over

            // Employment and leave tracking
            $table->date('employment_start_date')->nullable()->after('max_carry_over_days');
            $table->date('leave_year_start')->nullable()->after('employment_start_date'); // When their leave year starts (could be different from calendar year)
            $table->timestamp('last_leave_calculation')->nullable()->after('leave_year_start'); // Last time leave was calculated

            // Salary calculation fields
            $table->decimal('hourly_rate', 8, 2)->nullable()->after('last_leave_calculation'); // For salary calculations
            $table->decimal('daily_rate', 8, 2)->nullable()->after('hourly_rate'); // Daily salary rate
            $table->decimal('monthly_salary', 10, 2)->nullable()->after('daily_rate'); // Monthly base salary

            // Work schedule
            $table->decimal('standard_work_hours_per_day', 3, 1)->default(8.0)->after('monthly_salary');
            $table->integer('work_days_per_week')->default(5)->after('standard_work_hours_per_day');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'annual_leave_days',
                'sick_leave_days',
                'personal_leave_days',
                'monthly_leave_accrual',
                'monthly_sick_accrual',
                'current_leave_balance',
                'current_sick_balance',
                'current_personal_balance',
                'carried_over_days',
                'max_carry_over_days',
                'employment_start_date',
                'leave_year_start',
                'last_leave_calculation',
                'hourly_rate',
                'daily_rate',
                'monthly_salary',
                'standard_work_hours_per_day',
                'work_days_per_week'
            ]);
        });
    }
};
