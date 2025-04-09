<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushSystem extends Model
{
    protected $table = 'push_systems';

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
        'created_date'
    ];
}
