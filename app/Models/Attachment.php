<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attachment extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'card_id',
        'user_id',
        'title',
        'file_path',   // nếu là file thì lưu path
        'url',         // nếu là URL thì lưu ở đây
    ];
    
    public function card()
    {
        return $this->belongsTo(Card::class);
    }
  
}
