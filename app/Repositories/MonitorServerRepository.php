<?php

namespace App\Repositories;

use App\Models\MonitorServer;

class MonitorServerRepository extends BaseRepository
{
    public function model()
    {
        return MonitorServer::class;
    }
}
