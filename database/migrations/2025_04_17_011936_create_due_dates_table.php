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
        Schema::create('due_dates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('card_id');
    
            $table->dateTime('start_date')->nullable(); // không bắt buộc
            $table->dateTime('due_date'); // bắt buộc
            $table->integer('due_reminder')->nullable(); // phút trước hạn, ví dụ: 10 = nhắc trước 10 phút
            $table->integer('is_completed'); // đánh dấu hoàn thành
            $table->string('status_color')->nullable();
            $table->string('status_text')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('due_dates');
    }
};
