<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Server extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'ip'];

    public function monitorServers()
    {
        return $this->hasMany(MonitorServer::class);
    }

    public function apiKeys()
    {
        return $this->belongsToMany(ApiKey::class, 'server_api_keys');
    }
}
