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
        Schema::create('attendance_complaints', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('attendance_id');
            $table->enum('complaint_type', ['incorrect_time', 'missing_record', 'technical_issue', 'other']);
            $table->text('description');
            $table->enum('status', ['pending', 'under_review', 'resolved', 'rejected'])->default('pending');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->text('admin_response')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->json('proposed_changes')->nullable(); // Store what changes the employee is requesting
            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index(['attendance_id']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_complaints');
    }
};
