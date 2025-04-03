<?php

namespace App\Repositories;

use App\Models\DeviceFingerprint;

class DeviceFingerprintRepository extends BaseRepository
{
    public function model()
    {
        return DeviceFingerprint::class;
    }
}
