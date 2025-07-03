<?php

namespace App\Services;

use App\Models\File;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Carbon\Carbon;

class FileService
{
    protected S3Client $s3Client;
    protected string $bucket;
    protected string $defaultDisk;

    public function __construct()
    {
        $this->defaultDisk = 'seaweedfs';
        $this->bucket = config('filesystems.disks.seaweedfs.bucket');

        // Initialize S3Client for SeaweedFS
        $this->s3Client = new S3Client([
            'version' => 'latest',
            'region' => config('filesystems.disks.seaweedfs.region'),
            'endpoint' => config('filesystems.disks.seaweedfs.endpoint'),
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => config('filesystems.disks.seaweedfs.key'),
                'secret' => config('filesystems.disks.seaweedfs.secret'),
            ],
        ]);
    }

    /**
     * Generate a presigned URL for uploading a file.
     */
    public function generateUploadPresignedUrl(
        string $filename,
        string $mimeType,
        User $user,
        string $visibility = 'private',
        ?Carbon $expiresAt = null,
        array $metadata = []
    ): array {
        $key = $this->generateUniqueKey($filename, $user->id);
        $expirationTime = now()->addMinutes(15); // 15 minutes for upload

        try {
            $command = $this->s3Client->getCommand('PutObject', [
                'Bucket' => $this->bucket,
                'Key' => $key,
                'ContentType' => $mimeType,
                'Metadata' => array_merge($metadata, [
                    'user-id' => (string) $user->id,
                    'original-name' => $filename,
                    'visibility' => $visibility,
                ])
            ]);

            $presignedUrl = (string) $this->s3Client->createPresignedRequest(
                $command,
                $expirationTime
            )->getUri();

            // Create file record
            $file = File::create([
                'original_name' => $filename,
                'storage_disk' => $this->defaultDisk,
                'storage_path' => $key,
                'seaweedfs_key' => $key,
                'bucket' => $this->bucket,
                'mime_type' => $mimeType,
                'user_id' => $user->id,
                'visibility' => $visibility,
                'expires_at' => $expiresAt,
                'metadata' => $metadata,
            ]);

            return [
                'upload_url' => $presignedUrl,
                'file_id' => $file->id,
                'key' => $key,
                'expires_at' => $expirationTime->toISOString(),
                'headers' => [
                    'Content-Type' => $mimeType,
                ]
            ];
        } catch (AwsException $e) {
            throw new \Exception('Failed to generate upload URL: ' . $e->getMessage());
        }
    }

    /**
     * Generate a presigned URL for downloading a file.
     */
    public function generateDownloadPresignedUrl(
        File $file,
        ?User $user = null,
        int $expiresInMinutes = 60
    ): string {
        // Check access permissions
        if (!$file->isAccessibleBy($user)) {
            throw new \Exception('Access denied to this file');
        }

        // Check if file is expired
        if ($file->isExpired()) {
            throw new \Exception('File has expired');
        }

        try {
            $command = $this->s3Client->getCommand('GetObject', [
                'Bucket' => $file->bucket,
                'Key' => $file->seaweedfs_key,
                'ResponseContentDisposition' => 'attachment; filename="' . $file->original_name . '"',
                'ResponseContentType' => $file->mime_type,
            ]);

            $presignedUrl = (string) $this->s3Client->createPresignedRequest(
                $command,
                now()->addMinutes($expiresInMinutes)
            )->getUri();

            // Update download statistics
            $file->incrementDownloadCount();

            return $presignedUrl;
        } catch (AwsException $e) {
            throw new \Exception('Failed to generate download URL: ' . $e->getMessage());
        }
    }

    /**
     * Generate a presigned URL for streaming a file (inline viewing).
     */
    public function generateStreamPresignedUrl(
        File $file,
        ?User $user = null,
        int $expiresInMinutes = 60
    ): string {
        // Check access permissions
        if (!$file->isAccessibleBy($user)) {
            throw new \Exception('Access denied to this file');
        }

        // Check if file is expired
        if ($file->isExpired()) {
            throw new \Exception('File has expired');
        }

        try {
            $command = $this->s3Client->getCommand('GetObject', [
                'Bucket' => $file->bucket,
                'Key' => $file->seaweedfs_key,
                'ResponseContentType' => $file->mime_type,
                'ResponseContentDisposition' => 'inline; filename="' . $file->original_name . '"',
            ]);

            $presignedUrl = (string) $this->s3Client->createPresignedRequest(
                $command,
                now()->addMinutes($expiresInMinutes)
            )->getUri();

            // Update access time
            $file->update(['last_accessed_at' => now()]);

            return $presignedUrl;
        } catch (AwsException $e) {
            throw new \Exception('Failed to generate stream URL: ' . $e->getMessage());
        }
    }

    /**
     * Complete file upload and update metadata.
     */
    public function completeUpload(File $file, int $fileSize): File
    {
        try {
            // Get object metadata from SeaweedFS
            $result = $this->s3Client->headObject([
                'Bucket' => $file->bucket,
                'Key' => $file->seaweedfs_key,
            ]);

            // Calculate file hash for integrity
            $fileHash = $this->calculateFileHash($file);

            // Update file record
            $file->update([
                'size' => $fileSize,
                'file_hash' => $fileHash,
                'metadata' => array_merge($file->metadata ?? [], [
                    'etag' => $result['ETag'] ?? null,
                    'last_modified' => $result['LastModified'] ?? null,
                    'completed_at' => now()->toISOString(),
                ])
            ]);

            return $file;
        } catch (AwsException $e) {
            // If file doesn't exist, cleanup the database record
            $file->delete();
            throw new \Exception('Upload verification failed: ' . $e->getMessage());
        }
    }

    /**
     * Delete a file from SeaweedFS and database.
     */
    public function deleteFile(File $file, User $user): bool
    {
        // Check permissions (only owner or admin can delete)
        if ($file->user_id !== $user->id && !$user->hasRole('admin')) {
            throw new \Exception('Permission denied to delete this file');
        }

        try {
            // Delete from SeaweedFS
            $this->s3Client->deleteObject([
                'Bucket' => $file->bucket,
                'Key' => $file->seaweedfs_key,
            ]);

            // Delete from database
            $file->delete();

            return true;
        } catch (AwsException $e) {
            // Even if S3 deletion fails, we might want to remove from database
            // depending on your business logic
            throw new \Exception('Failed to delete file: ' . $e->getMessage());
        }
    }

    /**
     * Share a file with specific users or roles.
     */
    public function shareFile(
        File $file,
        User $user,
        array $userIds = [],
        array $roles = [],
        ?Carbon $expiresAt = null
    ): File {
        // Check permissions (only owner can share)
        if ($file->user_id !== $user->id) {
            throw new \Exception('Permission denied to share this file');
        }

        if (!empty($userIds)) {
            $file->grantAccessToUsers($userIds);
        }

        if (!empty($roles)) {
            $file->grantAccessToRoles($roles);
        }

        if ($expiresAt) {
            $file->expires_at = $expiresAt;
            $file->save();
        }

        return $file;
    }

    /**
     * Get file statistics for a user.
     */
    public function getUserFileStats(User $user): array
    {
        $files = File::where('user_id', $user->id);

        return [
            'total_files' => $files->count(),
            'total_size' => $files->sum('size'),
            'total_downloads' => $files->sum('download_count'),
            'by_visibility' => [
                'private' => $files->where('visibility', 'private')->count(),
                'public' => $files->where('visibility', 'public')->count(),
                'shared' => $files->where('visibility', 'shared')->count(),
            ],
            'recent_uploads' => $files->latest()->limit(5)->get(),
        ];
    }

    /**
     * Generate a unique key for the file.
     */
    protected function generateUniqueKey(string $filename, int $userId): string
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $timestamp = now()->format('Y/m/d');
        $uuid = Str::uuid();

        return "files/{$userId}/{$timestamp}/{$uuid}" . ($extension ? ".{$extension}" : '');
    }

    /**
     * Calculate file hash for integrity checking.
     */
    protected function calculateFileHash(File $file): ?string
    {
        try {
            // For now, we'll use ETag as hash
            // In production, you might want to download and calculate actual hash
            $result = $this->s3Client->headObject([
                'Bucket' => $file->bucket,
                'Key' => $file->seaweedfs_key,
            ]);

            return trim($result['ETag'] ?? '', '"');
        } catch (AwsException $e) {
            return null;
        }
    }

    /**
     * Check if SeaweedFS is healthy.
     */
    public function healthCheck(): bool
    {
        try {
            $this->s3Client->headBucket(['Bucket' => $this->bucket]);
            return true;
        } catch (AwsException $e) {
            return false;
        }
    }

    /**
     * Create bucket if it doesn't exist.
     */
    public function ensureBucketExists(): bool
    {
        try {
            $this->s3Client->headBucket(['Bucket' => $this->bucket]);
            return true;
        } catch (AwsException $e) {
            if ($e->getStatusCode() === 404) {
                try {
                    $this->s3Client->createBucket(['Bucket' => $this->bucket]);
                    return true;
                } catch (AwsException $createException) {
                    return false;
                }
            }
            return false;
        }
    }

    /**
     * Get file info without accessing the file content.
     */
    public function getFileInfo(File $file, ?User $user = null): array
    {
        if (!$file->isAccessibleBy($user)) {
            throw new \Exception('Access denied to this file');
        }

        return [
            'id' => $file->id,
            'name' => pathinfo($file->original_name, PATHINFO_FILENAME),
            'original_name' => $file->original_name,
            'size' => $file->size,
            'human_readable_size' => $file->human_readable_size,
            'mime_type' => $file->mime_type,
            'visibility' => $file->visibility,
            'download_count' => $file->download_count,
            'created_at' => $file->created_at,
            'last_accessed_at' => $file->last_accessed_at,
            'expires_at' => $file->expires_at,
            'is_expired' => $file->isExpired(),
            'metadata' => $file->metadata,
        ];
    }
}
