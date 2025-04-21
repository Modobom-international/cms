<?php

namespace App\Repositories;

use App\Models\TrackingEvent;
use App\Enums\Utility;

class TrackingEventRepository extends BaseRepository
{
    protected $utility;

    public function __construct(Utility $utility)
    {
        parent::__construct();
        $this->utility = $utility;
    }

    public function model()
    {
        return TrackingEvent::class;
    }

    public function getTrackingEventByDomain($domain, $date, $path)
    {
        $query = $this->model->where('domain', $domain)
            ->where('timestamp', '>=', $this->utility->covertDateTimeToMongoBSONDateGMT7($date . ' 00:00:00'))
            ->where('timestamp', '<=', $this->utility->covertDateTimeToMongoBSONDateGMT7($date . ' 23:59:59'));

        if ($path != 'all') {
            $query = $query->where('path', $path);
        }

        $query = $query->orderBy('timestamp', 'desc')
            ->get();

        return $query;
    }
}
