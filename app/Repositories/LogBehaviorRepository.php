<?php

namespace App\Repositories;

use App\Models\LogBehavior;

class LogBehaviorRepository extends BaseRepository
{
    public function model()
    {
        return LogBehavior::class;
    }
}
