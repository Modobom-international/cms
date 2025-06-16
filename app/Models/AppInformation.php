<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class AppInformation extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'config_pools';

    protected $fillable = ['request_id', 'app_name', 'app_id', 'app_version', 'os_name', 'os_version', 'event_name', 'event_value', 'category'];
}
