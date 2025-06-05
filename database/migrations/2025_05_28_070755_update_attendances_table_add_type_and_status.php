<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Drop existing columns only if they exist
            if (Schema::hasColumn('attendances', 'check_in')) {
                $table->dropColumn('check_in');
            }
            if (Schema::hasColumn('attendances', 'check_out')) {
                $table->dropColumn('check_out');
            }
        });

        // Modify existing type column or create it
        if (Schema::hasColumn('attendances', 'type')) {
            // Modify existing type column to ensure it has the correct enum values
            DB::statement("ALTER TABLE attendances MODIFY COLUMN type ENUM('full_day', 'half_day') NOT NULL");
        } else {
            Schema::table('attendances', function (Blueprint $table) {
                $table->enum('type', ['full_day', 'half_day'])->after('date');
            });
        }

        Schema::table('attendances', function (Blueprint $table) {
            // Add new columns as nullable first to handle existing data
            if (!Schema::hasColumn('attendances', 'checkin_time')) {
                $table->dateTime('checkin_time')->nullable()->after('type');
            }
            if (!Schema::hasColumn('attendances', 'checkout_time')) {
                $table->dateTime('checkout_time')->nullable()->after('checkin_time');
            }
            if (!Schema::hasColumn('attendances', 'total_work_hours')) {
                $table->decimal('total_work_hours', 5, 2)->nullable()->after('checkout_time');
            }
            if (!Schema::hasColumn('attendances', 'status')) {
                $table->enum('status', ['completed', 'incomplete'])->default('incomplete')->after('total_work_hours');
            }
        });

        // Set default values for existing records
        DB::table('attendances')->whereNull('checkin_time')->update([
            'checkin_time' => DB::raw('CONCAT(date, " 09:00:00")'),
            'checkout_time' => DB::raw('CONCAT(date, " 17:00:00")'),
            'total_work_hours' => 8.0,
            'status' => 'completed'
        ]);

        // Now make checkin_time not nullable
        DB::statement("ALTER TABLE attendances MODIFY COLUMN checkin_time DATETIME NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Drop new columns if they exist
            if (Schema::hasColumn('attendances', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('attendances', 'total_work_hours')) {
                $table->dropColumn('total_work_hours');
            }
            if (Schema::hasColumn('attendances', 'checkout_time')) {
                $table->dropColumn('checkout_time');
            }
            if (Schema::hasColumn('attendances', 'checkin_time')) {
                $table->dropColumn('checkin_time');
            }
            if (Schema::hasColumn('attendances', 'type')) {
                $table->dropColumn('type');
            }
        });

        Schema::table('attendances', function (Blueprint $table) {
            // Restore original columns only if they don't exist
            if (!Schema::hasColumn('attendances', 'check_in')) {
                $table->time('check_in')->after('date');
            }
            if (!Schema::hasColumn('attendances', 'check_out')) {
                $table->time('check_out')->nullable()->after('check_in');
            }
        });
    }
};
