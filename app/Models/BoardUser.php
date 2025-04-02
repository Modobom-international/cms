<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class BoardUser extends Pivot
{
    use HasFactory;
    
    protected $table = 'board_users';
    
    protected $fillable = [
        'board_id',
        'user_id',
        'role',
    ];
    
    /**
     * Định nghĩa quyền (role) trong bảng board_users
     */
    const ROLE_MEMBER = 'member';
    const ROLE_ADMIN = 'admin';
    
    /**
     * Quan hệ với User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Quan hệ với Board
     */
    public function board()
    {
        return $this->belongsTo(Board::class);
    }
}
