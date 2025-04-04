<?php

namespace App\Repositories;

use App\Models\TrackingEvent;

class TrackingEventRepository extends BaseRepository
{
    public function model()
    {
        return TrackingEvent::class;
    }

    public function getTrackingEventByDomain($domain)
    {
        return $this->model->where('domain', $domain)
            ->where('timestamp', '>=', Common::covertDateTimeToMongoBSONDateGMT7($date . ' 00:00:00'))
            ->where('timestamp', '<=', Common::covertDateTimeToMongoBSONDateGMT7($date . ' 23:59:59'))
            ->orderBy('timestamp', 'desc')
            ->get();
    }
}
