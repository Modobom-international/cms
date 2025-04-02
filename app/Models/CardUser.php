<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardUser extends Model
{
    use HasFactory;
    
    protected $table = 'card_users';
    
    protected $fillable = ['card_id', 'user_id'];
    
    public function card()
    {
        return $this->belongsTo(Card::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
