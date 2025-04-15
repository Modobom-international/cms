<?php

namespace App\Repositories;

use App\Models\HeartBeat;

class HeartBeatRepository extends BaseRepository
{
    public function model()
    {
        return HeartBeat::class;
    }

    public function getByUuidAndDomain($uuid, $domain)
    {
        return $this->model->where('uuid', $uuid)
            ->where('domain', $domain)
            ->first();
    }
}
