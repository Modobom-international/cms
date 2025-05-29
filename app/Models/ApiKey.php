<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'key_hash',
        'key_prefix',
        'user_id',
        'last_used_at',
        'expires_at',
        'is_active',
    ];

    protected $hidden = [
        'key_hash',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function generateKey(): array
    {
        // Generate a random string of 32 bytes and encode it in base64
        $randomBytes = random_bytes(32);
        $key = base64_encode($randomBytes);

        // Remove any non-alphanumeric characters and limit to 64 characters
        $key = preg_replace('/[^a-zA-Z0-9]/', '', $key);
        $key = substr($key, 0, 64);

        return [
            'key' => $key,
            'key_hash' => Hash::make($key),
            'key_prefix' => substr($key, 0, 8)
        ];
    }

    public function verifyKey(string $key): bool
    {
        return Hash::check($key, $this->key_hash);
    }

    public function isValid(): bool
    {
        return $this->is_active &&
            ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
