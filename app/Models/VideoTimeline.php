<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoTimeline extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'heartbeats';

    protected $fillable = ['uuid', 'domain', 'path', 'start_time', 'end_time', 'total_time', 'timeline', 'user_info'];
}
