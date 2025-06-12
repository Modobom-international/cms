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
        Schema::create('dns_records', function (Blueprint $table) {
            $table->id();
            $table->string('cloudflare_id')->unique()->comment('CloudFlare DNS record ID');
            $table->string('zone_id')->comment('CloudFlare Zone ID');
            $table->string('domain')->index()->comment('Domain name this record belongs to');
            $table->string('type', 10)->comment('DNS record type (A, CNAME, MX, etc.)');
            $table->string('name')->comment('DNS record name');
            $table->text('content')->comment('DNS record content/value');
            $table->integer('ttl')->default(1)->comment('Time to Live');
            $table->boolean('proxied')->default(false)->comment('Whether record is proxied through CloudFlare');
            $table->json('meta')->nullable()->comment('Additional metadata from CloudFlare');
            $table->text('comment')->nullable()->comment('CloudFlare record comment');
            $table->json('tags')->nullable()->comment('CloudFlare record tags');
            $table->timestamp('cloudflare_created_on')->nullable()->comment('When record was created in CloudFlare');
            $table->timestamp('cloudflare_modified_on')->nullable()->comment('When record was last modified in CloudFlare');
            $table->timestamps();

            // Indexes for performance
            $table->index(['domain', 'type']);
            $table->index(['zone_id', 'type']);
            $table->index('cloudflare_created_on');
            $table->index('cloudflare_modified_on');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dns_records');
    }
};
