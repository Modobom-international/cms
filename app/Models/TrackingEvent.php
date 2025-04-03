<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrackingEvent extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'heartbeats';

    protected $fillable = ['uuid', 'event_name', 'event_data', 'timestamp', 'user', 'domain', 'path'];
}
