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
        Schema::create('push_systems', function (Blueprint $table) {
            $table->id();
            $table->string('token', 255)->nullable();
            $table->string('app', 255)->nullable();
            $table->string('platform', 255)->nullable();
            $table->string('device', 255)->nullable();
            $table->string('country', 255)->nullable();
            $table->string('keyword', 255)->nullable();
            $table->string('shortcode', 255)->nullable();
            $table->string('telcoid', 255)->nullable();
            $table->string('network', 255)->nullable();
            $table->string('permission', 255)->nullable();
            $table->date('created_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_systems');
    }
};
