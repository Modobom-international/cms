<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsModelActivity;

class CheckList extends Model
{
    use LogsModelActivity;
    protected $table = 'checklists';
    protected $fillable = ['card_id', 'title'];
    
    public function card()
    {
        return $this->belongsTo(Card::class, 'card_id');
    }
    
    public function items()
    {
        return $this->hasMany(CheckListItem::class,'checklist_id');
    }
}
