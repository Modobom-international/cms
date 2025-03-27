<?php

namespace App\Repositories;

use App\Models\Widget;

class WidgetRepository extends BaseRepository
{
    public function model()
    {
        return Widget::class;
    }
}
