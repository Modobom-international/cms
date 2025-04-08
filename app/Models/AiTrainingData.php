<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class AiTrainingData extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'ai_training_data';

    protected $fillable = ['uuid', 'domain', 'session_start', 'session_end', 'events'];
}
