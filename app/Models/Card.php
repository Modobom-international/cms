<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    use HasFactory;
    
    protected $fillable = ['list_id', 'title', 'description', 'position'];
    
    public function listBoard()
    {
        return $this->belongsTo(ListBoard::class);
    }
    
    public function members()
    {
        return $this->belongsToMany(User::class, 'card_users', 'card_id', 'user_id');
    }
}
