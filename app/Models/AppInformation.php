<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class AppInformation extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'app_information';

    protected $fillable = [
        'user_id',
        'request_id',
        'app_name',
        'os_name',
        'os_version',
        'app_version',
        'category',
        'platform',
        'country',
        'event_name',
        'event_value',
        'created_at'
    ];
}
