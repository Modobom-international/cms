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
        Schema::create('employee_leave_entitlements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->integer('year'); // Leave year (e.g., 2025)
            $table->integer('month'); // Month (1-12)

            // Monthly leave allocation (1 day per month for official contract employees)
            $table->decimal('monthly_allocation', 3, 1)->default(1.0); // 1 day per month
            $table->decimal('days_earned', 3, 1)->default(0.0); // Days earned this month
            $table->decimal('days_used', 3, 1)->default(0.0); // Days used this month
            $table->decimal('days_remaining', 3, 1)->default(0.0); // Days remaining this month

            // Company policy: max 2 days per month can be used
            $table->decimal('max_monthly_usage', 3, 1)->default(2.0);

            // Employment status affecting entitlements
            $table->boolean('has_official_contract')->default(true); // Only official contract employees get paid leave
            $table->boolean('is_probation')->default(false); // Probation employees might have different rules

            // Tracking
            $table->decimal('carried_over_from_previous', 3, 1)->default(0.0); // No carry over as per policy
            $table->decimal('forfeited_days', 3, 1)->default(0.0); // Days lost due to no carry over policy

            // Auto calculation fields
            $table->boolean('is_calculated')->default(false); // Whether this month has been calculated
            $table->timestamp('calculated_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // End of month when days expire

            $table->timestamps();

            // Indexes
            $table->index(['employee_id', 'year', 'month']);
            $table->index(['year', 'month']);
            $table->index(['employee_id', 'is_calculated']);

            // Unique constraint - one record per employee per month
            $table->unique(['employee_id', 'year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_leave_entitlements');
    }
};
