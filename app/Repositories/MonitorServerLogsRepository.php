<?php

namespace App\Repositories;

use App\Models\MonitorServerLogs;

class MonitorServerLogsRepository extends BaseRepository
{
    public function model()
    {
        return MonitorServerLogs::class;
    }

    public function storeLog($data)
    {
        return $this->model->create($data);
    }
} 