<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    protected $table = 'domains';
    
    protected $fillable = [
        'domain',
        'time_expired',
        'registrar',
        'is_locked'
    ];
}
