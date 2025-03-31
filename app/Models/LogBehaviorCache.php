<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogBehaviorCache extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'log_behavior_cache';

    protected $fillable = [
        'data',
        'path',
        'total_path',
        'key'
    ];
}
