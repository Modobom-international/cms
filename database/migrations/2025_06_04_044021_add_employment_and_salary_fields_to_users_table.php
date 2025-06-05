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
            // Employment information
            $table->date('employment_start_date')->nullable()->after('profile_photo_path');
            $table->boolean('has_official_contract')->default(true)->after('employment_start_date');
            $table->boolean('is_probation')->default(false)->after('has_official_contract');
            $table->string('department')->nullable()->after('is_probation');
            $table->string('position')->nullable()->after('department');

            // Salary information
            $table->decimal('hourly_rate', 8, 2)->nullable()->after('position');
            $table->decimal('daily_rate', 8, 2)->nullable()->after('hourly_rate');
            $table->decimal('monthly_salary', 10, 2)->nullable()->after('daily_rate');

            // Work schedule
            $table->decimal('standard_work_hours_per_day', 3, 1)->default(8.0)->after('monthly_salary');
            $table->integer('work_days_per_week')->default(5)->after('standard_work_hours_per_day');

            // Status
            $table->boolean('is_active')->default(true)->after('work_days_per_week');

            // Indexes for better performance
            $table->index(['is_active']);
            $table->index(['has_official_contract']);
            $table->index(['is_probation']);
            $table->index(['department']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['has_official_contract']);
            $table->dropIndex(['is_probation']);
            $table->dropIndex(['department']);

            $table->dropColumn([
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
            ]);
        });
    }
};
