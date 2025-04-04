<?php

namespace App\Repositories;

use App\Models\Domain;

class DomainRepository extends BaseRepository
{
    public function model()
    {
        return Domain::class;
    }
}
