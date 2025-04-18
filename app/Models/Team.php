<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsModelActivity;

class Team extends Model
{
    use LogsModelActivity;

    protected $fillable = [
        'name'
    ];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'team_permission', 'team_id', 'permission_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_team', 'team_id', 'user_id');
    }
}
