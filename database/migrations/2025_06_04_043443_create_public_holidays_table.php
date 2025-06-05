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
        Schema::create('public_holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Holiday name (e.g., "New Year's Day", "National Day")
            $table->date('original_date'); // Original holiday date
            $table->date('observed_date'); // Actual observed date (adjusted if falls on weekend)
            $table->integer('year');
            $table->boolean('is_recurring')->default(true); // Whether this holiday repeats annually
            $table->enum('adjustment_rule', ['none', 'previous_workday', 'next_workday', 'company_decision'])->default('company_decision');

            // Holiday types
            $table->enum('holiday_type', ['public', 'company', 'new_year', 'religious', 'national'])->default('public');
            $table->boolean('is_paid')->default(true); // Whether employees get paid for this holiday
            $table->boolean('affects_salary')->default(false); // Whether this affects salary calculations

            // Work requirements
            $table->boolean('requires_coverage')->default(false); // If some employees need to work
            $table->text('coverage_requirements')->nullable(); // Details about who needs to work
            $table->decimal('overtime_multiplier', 3, 2)->default(2.0); // Pay multiplier for working on holiday

            // Metadata
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('affected_departments')->nullable(); // Which departments are affected

            $table->timestamps();

            // Indexes
            $table->index(['year', 'is_active']);
            $table->index(['observed_date']);
            $table->index(['holiday_type', 'is_active']);
            $table->unique(['name', 'year']); // Prevent duplicate holidays in same year
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('public_holidays');
    }
};
