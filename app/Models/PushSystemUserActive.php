<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushSystemUserActive extends Model
{
    protected $table = 'push_systems';

    protected $fillable = [
        'token',
        'country',
        'activated_at',
        'activated_date'
    ];
}
