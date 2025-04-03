<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsersTracking extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'users_tracking';

    protected $fillable = [
        'name',
        'price',
        'description'
    ];
}
