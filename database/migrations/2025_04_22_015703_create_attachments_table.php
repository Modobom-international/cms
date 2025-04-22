<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttachmentsTable extends Migration
{
    public function up()
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('card_id');
            $table->unsignedBigInteger('user_id');
            $table->string('title');
            $table->string('file_path')->nullable(); // Nếu là file
            $table->string('url')->nullable();       // Nếu là URL
            $table->timestamps();
            
            $table->foreign('card_id')->references('id')->on('cards')->onDelete('cascade');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('attachments');
    }
}
