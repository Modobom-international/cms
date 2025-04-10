<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HtmlSource extends Model
{
    protected $fillable = [
        'app_id',
        'version',
        'note',
        'device_id',
        'country',
        'platform',
        'source',
        'url',
        'created_date',
    ];
}
