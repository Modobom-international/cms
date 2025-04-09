<?php

namespace App\Repositories;

use App\Models\PushSystemCache;

class PushSystemCacheRepository extends BaseRepository
{
    public function model()
    {
        return PushSystemCache::class;
    }
}
