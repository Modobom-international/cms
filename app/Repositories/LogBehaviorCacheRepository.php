<?php

namespace App\Repositories;

use App\Models\LogBehaviorCache;

class LogBehaviorCacheRepository extends BaseRepository
{
    public function model()
    {
        return LogBehaviorCache::class;
    }

    public function deleteByKey($key)
    {
        return $this->model->where('key', $key)->delete();
    }

    public function getByKey($key)
    {
        return $this->model->where('key', $key)->get();
    }
}
