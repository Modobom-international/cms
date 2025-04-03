<?php

namespace App\Repositories;

use App\Models\HeartBeat;

class HeartBeatRepository extends BaseRepository
{
    public function model()
    {
        return HeartBeat::class;
    }
}
