<?php

namespace App\Models;

use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DueDate extends Model
{
    use HasFactory, LogsModelActivity;
    protected $fillable = ['card_id', 'start_date', 'due_date', 'due_reminder', 'is_completed', 'status_color', 'status_text'];
    
    
    public function card()
    {
        return $this->belongsTo(Card::class);
    }
}
