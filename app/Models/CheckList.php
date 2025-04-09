<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Checklist extends Model
{
    protected $fillable = ['card_id', 'title'];
    
    public function card()
    {
        return $this->belongsTo(Card::class, 'card_id');
    }
    
    public function items()
    {
        return $this->hasMany(ChecklistItem::class);
    }
}
