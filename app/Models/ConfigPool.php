<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class ConfigPool extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'config_pools';

    protected $fillable = ['key', 'data', 'description'];
}
