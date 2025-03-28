<?php

namespace App\Repositories;

use App\Models\Column;

class ColumnRepository extends BaseRepository
{
    public function model()
    {
        return Column::class;
    }
}
