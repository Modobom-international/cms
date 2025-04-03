<?php

namespace App\Repositories;

use App\Models\UsersTracking;

class UsersTrackingRepository extends BaseRepository
{
    public function model()
    {
        return UsersTracking::class;
    }
}
