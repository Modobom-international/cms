<?php

namespace App\Repositories;

use App\Models\CachePool;

class CachePoolRepository extends BaseRepository
{
    public function model()
    {
        return CachePool::class;
    }

    public function getCacheByKey($key)
    {
        return $this->model->where('key', $key)->first();
    }

    public function updateCacheByKey($key, $data)
    {
        return $this->model->where('key', $key)->update($data);
    }
}
