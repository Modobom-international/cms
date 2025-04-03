<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceFingerprint extends Model
{
    protected $fillable = [
        'user_id',
        'user_agent',
        'platform',
        'language',
        'cookies_enabled',
        'screen_width',
        'screen_height',
        'timezone',
        'fingerprint'
    ];
}
