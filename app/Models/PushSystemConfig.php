<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushSystemConfig extends Model
{
    protected $table = 'push_system_configs';

    protected $fillable = [
        'push_count',
        'config_links',
        'status',
        'type',
        'share'
    ];
}
