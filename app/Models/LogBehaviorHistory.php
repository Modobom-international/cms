<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogBehaviorHistory extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'log_behavior_history';

    protected $fillable = [
        'data',
        'path',
        'total_path',
        'key'
    ];
}
