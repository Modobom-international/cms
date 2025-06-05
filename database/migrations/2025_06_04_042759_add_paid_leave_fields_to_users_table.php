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
            if (!Schema::hasColumn('users', 'annual_leave_days')) {
                $table->decimal('annual_leave_days', 5, 2)->default(25.00)->after('profile_photo_path'); // Total annual leave days
            }
            if (!Schema::hasColumn('users', 'sick_leave_days')) {
                $table->decimal('sick_leave_days', 5, 2)->default(12.00)->after('annual_leave_days'); // Annual sick leave days
            }
            if (!Schema::hasColumn('users', 'personal_leave_days')) {
                $table->decimal('personal_leave_days', 5, 2)->default(5.00)->after('sick_leave_days'); // Personal leave days
            }

            // Monthly accrual rates (how many days earned per month)
            if (!Schema::hasColumn('users', 'monthly_leave_accrual')) {
                $table->decimal('monthly_leave_accrual', 5, 2)->default(2.08)->after('personal_leave_days'); // 25/12 = 2.08 days per month
            }
            if (!Schema::hasColumn('users', 'monthly_sick_accrual')) {
                $table->decimal('monthly_sick_accrual', 5, 2)->default(1.00)->after('monthly_leave_accrual'); // 1 day per month
            }

            // Current balances (remaining days)
            if (!Schema::hasColumn('users', 'current_leave_balance')) {
                $table->decimal('current_leave_balance', 5, 2)->default(0.00)->after('monthly_sick_accrual');
            }
            if (!Schema::hasColumn('users', 'current_sick_balance')) {
                $table->decimal('current_sick_balance', 5, 2)->default(0.00)->after('current_leave_balance');
            }
            if (!Schema::hasColumn('users', 'current_personal_balance')) {
                $table->decimal('current_personal_balance', 5, 2)->default(0.00)->after('current_sick_balance');
            }

            // Carried over from previous year
            if (!Schema::hasColumn('users', 'carried_over_days')) {
                $table->decimal('carried_over_days', 5, 2)->default(0.00)->after('current_personal_balance');
            }
            if (!Schema::hasColumn('users', 'max_carry_over_days')) {
                $table->integer('max_carry_over_days')->default(5)->after('carried_over_days'); // Maximum days that can be carried over
            }

            // Leave tracking
            if (!Schema::hasColumn('users', 'leave_year_start')) {
                $table->date('leave_year_start')->nullable()->after('max_carry_over_days'); // When their leave year starts (could be different from calendar year)
            }
            if (!Schema::hasColumn('users', 'last_leave_calculation')) {
                $table->timestamp('last_leave_calculation')->nullable()->after('leave_year_start'); // Last time leave was calculated
            }
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
                'leave_year_start',
                'last_leave_calculation'
            ]);
        });
    }
};
