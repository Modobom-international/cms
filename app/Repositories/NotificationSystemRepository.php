<?php

namespace App\Repositories;

use App\Models\NotificationSystem;

class NotificationSystemRepository extends BaseRepository
{
    public function model()
    {
        return NotificationSystem::class;
    }
}
