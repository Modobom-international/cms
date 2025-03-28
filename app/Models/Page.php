<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'provider'
    ];

    public function rows()
    {
        return $this->hasMany(Row::class, 'page_id');
    }
}
