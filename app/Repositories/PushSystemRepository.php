<?php

namespace App\Repositories;

use App\Models\PushSystem;

class PushSystemRepository extends BaseRepository
{
    public function model()
    {
        return PushSystem::class;
    }
}
