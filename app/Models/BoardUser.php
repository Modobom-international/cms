<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoardUser extends Model
{
    use HasFactory;
    
    protected $fillable = ['board_id', 'user_id', 'role'];
    
    public function board()
    {
        return $this->belongsTo(Board::class, 'board_id');
    }
    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
