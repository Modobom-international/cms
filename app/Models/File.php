<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class File extends Model
{
    protected $fillable = [
        'size',
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
    ];

    protected $casts = [
        'access_permissions' => 'array',
        'metadata' => 'array',
        'expires_at' => 'datetime',
        'last_accessed_at' => 'datetime',
        'size' => 'integer',
        'download_count' => 'integer'
    ];

    protected $dates = [
        'expires_at',
        'last_accessed_at'
    ];

    /**
     * Get the user that owns the file.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the file is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if the file is accessible by a user.
     */
    public function isAccessibleBy(?User $user = null): bool
    {
        // Public files are accessible to everyone
        if ($this->visibility === 'public') {
            return true;
        }

        // Check if file is expired
        if ($this->isExpired()) {
            return false;
        }

        // If no user provided, only public files are accessible
        if (!$user) {
            return false;
        }

        // Owner can always access
        if ($this->user_id === $user->id) {
            return true;
        }

        // Check shared permissions
        if ($this->visibility === 'shared' && $this->access_permissions) {
            return in_array($user->id, $this->access_permissions['users'] ?? []) ||
                !empty(array_intersect($user->roles->pluck('name')->toArray(), $this->access_permissions['roles'] ?? []));
        }

        return false;
    }

    /**
     * Grant access to specific users.
     */
    public function grantAccessToUsers(array $userIds): void
    {
        $permissions = $this->access_permissions ?? [];
        $permissions['users'] = array_unique(array_merge($permissions['users'] ?? [], $userIds));
        $this->access_permissions = $permissions;
        $this->visibility = 'shared';
        $this->save();
    }

    /**
     * Grant access to specific roles.
     */
    public function grantAccessToRoles(array $roles): void
    {
        $permissions = $this->access_permissions ?? [];
        $permissions['roles'] = array_unique(array_merge($permissions['roles'] ?? [], $roles));
        $this->access_permissions = $permissions;
        $this->visibility = 'shared';
        $this->save();
    }

    /**
     * Revoke access from users.
     */
    public function revokeAccessFromUsers(array $userIds): void
    {
        $permissions = $this->access_permissions ?? [];
        $permissions['users'] = array_diff($permissions['users'] ?? [], $userIds);
        $this->access_permissions = $permissions;
        $this->save();
    }

    /**
     * Revoke access from roles.
     */
    public function revokeAccessFromRoles(array $roles): void
    {
        $permissions = $this->access_permissions ?? [];
        $permissions['roles'] = array_diff($permissions['roles'] ?? [], $roles);
        $this->access_permissions = $permissions;
        $this->save();
    }

    /**
     * Increment download count and update last accessed time.
     */
    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
        $this->update(['last_accessed_at' => now()]);
    }

    /**
     * Get the full storage path.
     */
    public function getStoragePath(): string
    {
        return $this->storage_path ?: $this->seaweedfs_key;
    }

    /**
     * Get human readable file size.
     */
    protected function humanReadableSize(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->formatBytes($this->size)
        );
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Scope for files accessible by user.
     */
    public function scopeAccessibleBy($query, ?User $user = null)
    {
        if (!$user) {
            return $query->where('visibility', 'public');
        }

        return $query->where(function ($q) use ($user) {
            $q->where('visibility', 'public')
                ->orWhere('user_id', $user->id)
                ->orWhere(function ($subQ) use ($user) {
                    $subQ->where('visibility', 'shared')
                        ->where(function ($permQ) use ($user) {
                            $permQ->whereJsonContains('access_permissions->users', $user->id);
                            if ($user->roles->count() > 0) {
                                $userRoles = $user->roles->pluck('name')->toArray();
                                foreach ($userRoles as $role) {
                                    $permQ->orWhereJsonContains('access_permissions->roles', $role);
                                }
                            }
                        });
                });
        })->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope for files by visibility.
     */
    public function scopeByVisibility($query, string $visibility)
    {
        return $query->where('visibility', $visibility);
    }

    /**
     * Scope for files by storage disk.
     */
    public function scopeByStorageDisk($query, string $disk)
    {
        return $query->where('storage_disk', $disk);
    }

    /**
     * Scope for non-expired files.
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }
}
