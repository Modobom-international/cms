<?php

namespace App\Repositories;

use App\Models\ActivityLog;

class ActivityLogRepository extends BaseRepository
{
    public function model()
    {
        return ActivityLog::class;
    }

    public function getByFilter($filter)
    {
        $query = $this->model->whereDate('created_at', $filter['date']);

        if (isset($filter['action'])) {
            $query->where('action', $filter['action']);
        }

        if (isset($filter['user_id'])) {
            $query->where('user_id', $filter['user_id']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}
