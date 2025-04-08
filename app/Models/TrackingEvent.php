<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class TrackingEvent extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'tracking_events';

    protected $fillable = ['uuid', 'event_name', 'event_data', 'timestamp', 'user', 'domain', 'path'];
}
