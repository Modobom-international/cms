<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushSystemSetting extends Model
{
    protected $table = 'push_system_settings';

    protected $fillable = [
        'ip',
        'user_agent',
        'created_date',
        'keyword_dtac',
        'keyword_ais',
        'share_web',
        'link_web',
        'data',
        'domain',
    ];
}
