<?php

namespace App\Repositories;

use App\Models\MonitorServer;

class MonitorServerRepository extends BaseRepository
{
    public function model()
    {
        return MonitorServer::class;
    }

    public function getByServer($server_id)
    {
        return $this->model->where('server_id', $server_id)->orderBy('created_at', 'desc')->get();
    }
}
