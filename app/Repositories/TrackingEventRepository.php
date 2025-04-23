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
            ->whereDate('created_at',  $date);

        if ($path != 'all') {
            $query = $query->where('path', $path);
        }

        $query = $query->orderBy('created_at', 'desc')->get();

        return $query;
    }
}
