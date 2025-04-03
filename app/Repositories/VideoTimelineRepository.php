<?php

namespace App\Repositories;

use App\Models\VideoTimeline;

class VideoTimelineRepository extends BaseRepository
{
    public function model()
    {
        return VideoTimeline::class;
    }
}
