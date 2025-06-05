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
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->enum('leave_type', ['sick', 'vacation', 'personal', 'maternity', 'paternity', 'emergency', 'remote_work', 'other']);
            $table->enum('request_type', ['absence', 'remote_work']); // New field to distinguish between absence and remote work
            $table->date('start_date');
            $table->date('end_date');
            $table->time('start_time')->nullable(); // For partial day leaves
            $table->time('end_time')->nullable(); // For partial day leaves
            $table->boolean('is_full_day')->default(true);
            $table->text('reason');
            $table->text('additional_notes')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->text('approval_notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->json('remote_work_details')->nullable(); // Store remote work specific details like location, equipment needed, etc.
            $table->decimal('total_days', 5, 2); // Total number of days requested
            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index(['start_date', 'end_date']);
            $table->index(['leave_type']);
            $table->index(['request_type']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
