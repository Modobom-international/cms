<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsModelActivity;

class Card extends Model
{
    use HasFactory, LogsModelActivity;
    
    protected $fillable = ['list_id', 'title', 'description', 'position'];
    
    public function listBoard()
    {
        return $this->belongsTo(ListBoard::class,'list_id' );
    }
    
    public function members()
    {
        return $this->belongsToMany(User::class, 'card_users', 'card_id', 'user_id');
    }
    
    public function labels()
    {
        return $this->belongsToMany(Label::class, 'card_labels') ->withTimestamps();
    }
    
    public function checklists()
    {
        return $this->hasMany(Checklist::class);
    }
    
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
    
    public function dueDate()
    {
        return $this->hasOne(DueDate::class);
    }
}
