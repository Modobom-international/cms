<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class AppInformation extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'app_information';

    protected $guarded = [];
}
