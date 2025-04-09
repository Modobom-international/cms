<?php

namespace App\Repositories;

use App\Models\RequestGetSystemSetting;

class RequestGetSystemSettingRepository extends BaseRepository
{
    public function model()
    {
        return RequestGetSystemSetting::class;
    }
}
