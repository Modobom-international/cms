<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class VideoTimeline extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'video_timelines';

    protected $fillable = ['uuid', 'domain', 'path', 'start_time', 'end_time', 'total_time', 'timeline', 'user_info'];
}
