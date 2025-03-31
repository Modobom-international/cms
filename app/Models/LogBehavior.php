<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogBehavior extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'log_behavior';

    protected $fillable = [
        'name',
        'price',
        'description'
    ];
}
