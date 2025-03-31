<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HtmlSource extends Model
{
    protected $collection = 'html_sources';

    protected $fillable = [
        'url',
        'source',
        'app_id',
        'version',
        'note',
        'created_at',
        'device_id',
        'country',
        'platform',
        'created_date'
    ];
}
