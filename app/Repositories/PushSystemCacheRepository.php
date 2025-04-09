<?php

namespace App\Repositories;

use App\Models\PushSystemCache;

class PushSystemCacheRepository extends BaseRepository
{
    public function model()
    {
        return PushSystemCache::class;
    }

    public function getFirstByKey($key)
    {
        return $this->model->where('key', $key)->first();
    }

    public function updateTotalByKey($key, $data)
    {
        return $this->model->where('key', $key)->update($data);
    }

    public function getFirstByKeyLike($key)
    {
        return $this->model->where('key', 'LIKE', $key)->first();
    }

    public function getByKeyLike()
    {
        return $this->model->where('key', 'LIKE', $key)->get();
    }
}
