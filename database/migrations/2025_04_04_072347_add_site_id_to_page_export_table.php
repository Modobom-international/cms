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
        Schema::table('page_exports', function (Blueprint $table) {
            $table->foreignId('site_id')->after('id')->constrained()->onDelete('cascade');
            $table->string('result_path')->after('slugs')->nullable();
            $table->string('status')->after('result_path')->default('pending');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('page_exports', function (Blueprint $table) {
            $table->dropForeign(['site_id']);
            $table->dropColumn(['site_id', 'result_path', 'status']);
        });
    }
};
