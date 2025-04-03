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
        Schema::table('sites', function (Blueprint $table) {
            $table->string('name')->after('domain');
            $table->text('description')->nullable()->after('name');
            $table->string('cloudflare_project_name')->after('description');
            $table->string('cloudflare_domain_status')->default('pending')->after('cloudflare_project_name');
            $table->string('branch')->default('main')->after('cloudflare_domain_status');
            $table->foreignId('user_id')->constrained()->after('branch');
            $table->enum('status', ['active', 'inactive'])->default('active')->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn([
                'name',
                'description',
                'cloudflare_project_name',
                'cloudflare_domain_status',
                'branch',
                'user_id',
                'status'
            ]);
        });
    }
};