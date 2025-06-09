<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonitorServerLogs extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'monitor_server';

    protected $fillable = ['cpu', 'memory', 'disk', 'services', 'logs', 'timestamp', 'server_id'];
}
