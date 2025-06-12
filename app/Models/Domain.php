<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    protected $fillable = [
        'domain',
        'time_expired',
        'registrar',
        'is_locked',
        'renewable',
        'status',
        'name_servers',
        'renew_deadline',
        'registrar_created_at'
    ];

    public function sites()
    {
        return $this->belongsTo(Site::class, 'domain', 'domain');
    }

    /**
     * Get the DNS records for this domain
     */
    public function dnsRecords()
    {
        return $this->hasMany(DnsRecord::class, 'domain', 'domain');
    }
}
