<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Workspace extends Model
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
        'owner_id',
        'visibility',
    ];

    /**
     * Get the owner of the workspace.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get the users that are members of the workspace.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'workspace_users')
            ->withPivot('role')
            ->withTimestamp('created_at');
    }

    /**
     * Get the boards in the workspace.
     */
    public function boards()
    {
        return $this->hasMany(Board::class);
    }
}
