<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushSystemCache extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'push_system_caches';

    protected $fillable = [
        'token',
        'app',
        'platform',
        'device',
        'country',
        'keyword',
        'shortcode',
        'telcoid',
        'network',
        'permission',
        'created_at',
        'created_date'
    ];
}
