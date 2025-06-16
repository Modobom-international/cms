<?php

namespace App\Repositories;

use App\Models\AppInformation;

class AppInformationRepository extends BaseRepository
{
    public function model()
    {
        return AppInformation::class;
    }
}
