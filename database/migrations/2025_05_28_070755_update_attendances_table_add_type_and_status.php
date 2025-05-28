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
        Schema::table('attendances', function (Blueprint $table) {
            // Drop existing columns
            $table->dropColumn(['check_in', 'check_out']);

            // Add new columns
            $table->enum('type', ['full_day', 'half_day'])->after('date');
            $table->dateTime('checkin_time')->after('type');
            $table->dateTime('checkout_time')->nullable()->after('checkin_time');
            $table->decimal('total_work_hours', 5, 2)->nullable()->after('checkout_time');
            $table->enum('status', ['completed', 'incomplete'])->default('incomplete')->after('total_work_hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Drop new columns
            $table->dropColumn(['type', 'checkin_time', 'checkout_time', 'total_work_hours', 'status']);

            // Restore original columns
            $table->time('check_in')->after('date');
            $table->time('check_out')->nullable()->after('check_in');
        });
    }
};
