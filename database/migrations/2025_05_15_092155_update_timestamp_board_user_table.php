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
        Schema::table('board_users', function (Blueprint $table) {
            // Add timestamps (created_at and updated_at)
            $table->timestamp("updated_at")->useCurrent();


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('board_users', function (Blueprint $table) {
            $table->dropColumn('updated_at');
        });
    }
};