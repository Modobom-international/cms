<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class HeartBeat extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'heartbeats';

    protected $fillable = ['uuid', 'timestamp', 'domain', 'path', 'user_info'];
}
