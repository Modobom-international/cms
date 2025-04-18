<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\LogsModelActivity;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, LogsModelActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'type_user',
        'role',
        'profile_photo_path'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'id',
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the boards owned by the user.
     */
    public function workspaces()
    {
        return $this->hasMany(Workspace::class, 'owner_id');
    }

    public function ownedBoards()
    {
        return $this->hasMany(Board::class, 'owner_id');
    }

    /**
     * Get the boards that the user is a member of.
     */
    public function boards()
    {
        return $this->belongsToMany(Board::class, 'board_users')
            ->withPivot('role') // Lưu vai trò của user trong board
            ->withTimestamps(); // ✅ Dùng withTimestamps() (có 's')
    }

    /**
     * Get the comments created by the user.
     */
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function deviceFingerprints()
    {
        return $this->hasMany(DeviceFingerprint::class);
    }

    public function cards()
    {
        return $this->belongsToMany(Card::class, 'card_users', 'user_id', 'card_id')->withTimestamps();
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_id', 'id');
    }
}
