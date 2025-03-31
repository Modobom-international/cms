<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogBehavior extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'products';

    protected $fillable = ['name', 'price', 'description'];
}
