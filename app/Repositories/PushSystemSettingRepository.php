<?php

namespace App\Repositories;

use App\Models\PushSystemSetting;

class PushSystemSettingRepository extends BaseRepository
{
    public function model()
    {
        return PushSystemSetting::class;
    }
}
