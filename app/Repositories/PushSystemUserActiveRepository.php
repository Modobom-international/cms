<?php

namespace App\Repositories;

use App\Models\PushSystemUserActive;

class PushSystemUserActiveRepository extends BaseRepository
{
    public function model()
    {
        return PushSystemUserActive::class;
    }
}
