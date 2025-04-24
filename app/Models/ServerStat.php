<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class ServerStat extends Model
{
    protected $connection = 'mongodb';

    protected $collection = "server_stats";

    protected $fillable = ['cpu', 'ram', 'disk', 'timestamp'];
}
