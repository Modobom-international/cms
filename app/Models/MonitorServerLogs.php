<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class MonitorServerLogs extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'monitor_server_logs';
    protected $guarded = [];
}
