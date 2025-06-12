<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DnsRecord extends Model
{
    protected $fillable = [
        'cloudflare_id',
        'zone_id',
        'domain',
        'type',
        'name',
        'content',
        'ttl',
        'proxied',
        'meta',
        'comment',
        'tags',
        'cloudflare_created_on',
        'cloudflare_modified_on'
    ];

    protected $casts = [
        'proxied' => 'boolean',
        'meta' => 'array',
        'tags' => 'array',
        'cloudflare_created_on' => 'datetime',
        'cloudflare_modified_on' => 'datetime',
    ];

    /**
     * Get the domain that owns this DNS record
     */
    public function domainModel(): BelongsTo
    {
        return $this->belongsTo(Domain::class, 'domain', 'domain');
    }

    /**
     * Scope for filtering by domain
     */
    public function scopeForDomain($query, $domain)
    {
        return $query->where('domain', $domain);
    }

    /**
     * Scope for filtering by DNS record type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for proxied records only
     */
    public function scopeProxied($query, $proxied = true)
    {
        return $query->where('proxied', $proxied);
    }
}
