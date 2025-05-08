<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class MonitorServer extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'monitor_server';

    protected $fillable = ['cpu', 'memory', 'disk', 'services', 'logs', 'timestamp', 'server_id'];
}
