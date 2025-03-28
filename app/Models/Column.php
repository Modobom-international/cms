<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Column extends Model
{
    protected $fillable = [
        'row_id',
        'order',
        'settings'
    ];

    public function widgets()
    {
        return $this->hasMany(Widget::class, 'column_id');
    }
}
