<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Geolocation extends Model
{
    protected $fillable = ['latitude', 'longitude', 'city'];
}
