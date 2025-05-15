<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsModelActivity;

class ListBoard extends Model
{
    use HasFactory, LogsModelActivity;

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
        return $this->hasMany(Card::class, 'list_id');
    }

    public function board()
    {
        return $this->belongsTo(Board::class);
    }
}
