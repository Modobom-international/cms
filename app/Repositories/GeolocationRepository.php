<?php

namespace App\Repositories;

use App\Models\Geolocation;

class GeolocationRepository extends BaseRepository
{
    public function model()
    {
        return Geolocation::class;
    }
}
