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

    public function getByServerWithDate($server_id, $startDate, $endDate, $limit = 100)
    {
        return $this->model->where('server_id', $server_id)->whereBetween('created_at', [$startDate, $endDate])->orderBy('created_at', 'desc')->limit($limit)->get();
    }
}
