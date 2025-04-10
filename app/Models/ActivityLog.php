<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'action',
        'details',
        'user_id',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function users()
    {
        return $this->belongsTo(User::class);
    }
}
