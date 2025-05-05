<?php

namespace App\Repositories;

use App\Models\ConfigPool;

class ConfigPoolRepository extends BaseRepository
{
    public function model()
    {
        return ConfigPool::class;
    }

    public function getByKey($key)
    {
        return $this->model->where('key', $key)->first();
    }

    public function updateByKey($key, $data)
    {
        return $this->model->where('key', $key)->update($data);
    }
}
