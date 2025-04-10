<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->boolean('renewable')->default(false);
            $table->string('status', 10);
            $table->string('name_servers')->nullable();
            $table->dateTime('renew_deadline')->nullable();
            $table->dateTime('registrar_created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->dropColumn('renewable');
            $table->dropColumn('status');
            $table->dropColumn('name_servers');
            $table->dropColumn('renew_deadline');
            $table->dropColumn('registrar_created_at');
        });
    }
};
