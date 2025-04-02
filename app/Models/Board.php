<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Board extends Model
{

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'visibility',
        'owner_id',
        'workspace_id',
    ];

    /**
     * Get the owner of the board.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get the workspace that the board belongs to.
     */
    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the users that are members of the board.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'board_users')
            ->withPivot('role')
            ->withTimestamps(); // ✅ Dùng withTimestamps() (có 's')
    }




}