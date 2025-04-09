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
            $table->string('token')->nullable();
            $table->string('app')->nullable();
            $table->string('platform')->nullable();
            $table->string('device')->nullable();
            $table->string('country')->nullable();
            $table->string('keyword')->nullable();
            $table->string('shortcode')->nullable();
            $table->string('telcoid')->nullable();
            $table->string('network')->nullable();
            $table->string('permission')->nullable();
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
