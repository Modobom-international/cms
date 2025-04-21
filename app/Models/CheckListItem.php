<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckListItem extends Model
{
    protected $table = 'checklist_items';
    protected $fillable = ['checklist_id', 'content', 'is_completed'];

    
    public function checklist()
    {
        return $this->belongsTo(CheckList::class,'checklist_id');
    }
}

