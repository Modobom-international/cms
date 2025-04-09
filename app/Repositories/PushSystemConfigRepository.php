<?php

namespace App\Repositories;

use App\Models\PushSystemConfig;
use DB;

class PushSystemConfigRepository extends BaseRepository
{
    public function model()
    {
        return PushSystemConfig::class;
    }

    public function getConfigDataRaw()
    {
        return $this->model->where('push_count', "!=", 0)->get();
    }

    public function getConfigPushRow()
    {
        return $this->model->where('push_count', 0)->first();
    }

    public function getCountCurrentPush()
    {
        return $this->model->select(DB::raw('max(push_count) as count'))->first('count');
    }
}
