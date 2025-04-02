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
        Schema::create('device_fingerprints', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->string('user_agent');
            $table->string('platform')->nullable();
            $table->string('language')->nullable();
            $table->boolean('cookies_enabled')->default(true);
            $table->integer('screen_width')->nullable();
            $table->integer('screen_height')->nullable();
            $table->string('timezone')->nullable();
            $table->string('fingerprint')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_fingerprints');
    }
};
