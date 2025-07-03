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
        Schema::table('files', function (Blueprint $table) {
            // SeaweedFS storage fields
            $table->string('storage_disk')->default('seaweedfs');
            $table->string('storage_path')->nullable();
            $table->string('seaweedfs_key')->unique()->nullable();
            $table->string('bucket')->default('files');
            $table->string('mime_type')->nullable();
            $table->string('original_name')->nullable();
            $table->string('file_hash')->nullable();

            // Access control fields
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('visibility', ['private', 'public', 'shared'])->default('private');
            $table->json('access_permissions')->nullable(); // Store user/role permissions
            $table->timestamp('expires_at')->nullable();

            // Metadata fields
            $table->json('metadata')->nullable();
            $table->bigInteger('download_count')->default(0);
            $table->timestamp('last_accessed_at')->nullable();

            // Indexes for performance
            $table->index(['user_id', 'visibility']);
            $table->index(['storage_disk', 'storage_path']);
            $table->index('seaweedfs_key');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->dropIndex(['expires_at']);
            $table->dropIndex(['seaweedfs_key']);
            $table->dropIndex(['storage_disk', 'storage_path']);
            $table->dropIndex(['user_id', 'visibility']);

            $table->dropColumn([
                'storage_disk',
                'storage_path',
                'seaweedfs_key',
                'bucket',
                'mime_type',
                'original_name',
                'file_hash',
                'user_id',
                'visibility',
                'access_permissions',
                'expires_at',
                'metadata',
                'download_count',
                'last_accessed_at'
            ]);
        });
    }
};
