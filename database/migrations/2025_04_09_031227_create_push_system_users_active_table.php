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
        Schema::create('push_system_users_active', function (Blueprint $table) {
            $table->id();
            $table->string('token');
            $table->string('country');
            $table->dateTime('activated_at');
            $table->date('activated_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_system_users_active');
    }
};
