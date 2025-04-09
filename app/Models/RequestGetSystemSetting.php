<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestGetSystemSetting extends Model
{
    protected $table = 'request_get_system_settings';

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
