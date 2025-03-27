<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Row extends Model
{
    protected $fillable = [
        'page_id',
        'order',
        'settings'
    ];

    public function columns()
    {
        return $this->hasMany(Column::class, 'row_id');
    }
}
