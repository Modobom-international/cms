<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsModelActivity;

class Page extends Model
{
    use HasFactory, LogsModelActivity;

    protected $fillable = [
        'site_id',
        'name',
        'slug',
        'content',
        'provider'
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }
}
