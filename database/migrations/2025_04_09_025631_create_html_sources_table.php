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
        Schema::create('html_sources', function (Blueprint $table) {
            $table->id();
            $table->string('app_id', 255)->nullable();
            $table->string('version', 255)->nullable();
            $table->string('note')->nullable();
            $table->string('device_id', 191)->nullable();
            $table->string('country', 191)->nullable();
            $table->string('platform', 191)->nullable();
            $table->longText('source');
            $table->text('url');
            $table->date('created_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('html_sources');
    }
};
