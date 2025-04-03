<?php

namespace App\Repositories;

use App\Models\AiTrainingData;

class AiTrainingDataRepository extends BaseRepository
{
    public function model()
    {
        return AiTrainingData::class;
    }
}
