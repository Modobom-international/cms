<?php

namespace App\Repositories;

use App\Models\Row;

class RowRepository extends BaseRepository
{
    public function model()
    {
        return Row::class;
    }
}
