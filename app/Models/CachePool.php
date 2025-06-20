<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class CachePool extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'cache_pools';

    protected $fillable = ['key', 'data', 'description'];
}
