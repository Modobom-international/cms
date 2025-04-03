<?php

namespace App\Repositories;

use App\Models\TrackingEvent;

class TrackingEventRepository extends BaseRepository
{
    public function model()
    {
        return TrackingEvent::class;
    }
}
