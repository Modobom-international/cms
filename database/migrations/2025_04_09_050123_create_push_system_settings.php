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
        Schema::create('push_system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->date('created_date');
            $table->string('keyword_dtac')->nullable();
            $table->string('keyword_ais')->nullable();
            $table->string('share_web')->nullable();
            $table->string('link_web')->nullable();
            $table->longText('data')->nullable();
            $table->string('domain')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_system_settings');
    }
};
