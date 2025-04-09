<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushSystemUserActive extends Model
{
    protected $table = 'push_system_user_actives';

    protected $fillable = [
        'token',
        'country',
        'activated_at',
        'activated_date'
    ];
}
