<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Board extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'workspace_id',
        'name',
        'description',
        'visibility',
        'owner_id',
    ];
    
    /**
     * Quan hệ với Workspace
     */
    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }
    
    /**
     * Quan hệ với User (người sở hữu board)
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
    
    /**
     * Quan hệ với các user trong board thông qua bảng board_users
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'board_users')
            ->withPivot('role')
            ->withTimestamps();
    }
    
    /**
     * Quan hệ với List trong board
     */
    public function lists()
    {
        return $this->hasMany(ListBoard::class);
    }
}
