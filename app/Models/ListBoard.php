<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListBoard extends Model
{

    use HasFactory;
    protected $table = 'lists';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'board_id',
        'position',
    ];
    
    public function cards()
    {
        return $this->hasMany(Card::class);
    }
    
    public function boards()
    {
        return $this->belongsTo(ListBoard::class);
    }
}
