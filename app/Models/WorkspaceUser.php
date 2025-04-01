<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkspaceUser extends Model
{
    use HasFactory;
    
    protected $fillable = ['workspace_id', 'user_id', 'role', 'created_at'];
    public $timestamps = false;
    
    public function users()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    
    public function workspace()
    {
        return $this->belongsTo(Workspace::class, 'workspace_id', 'id');
    }
}
